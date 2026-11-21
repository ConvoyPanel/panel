<?php

namespace App\Providers;

use App\Auth\Socialite\OidcProvider;
use App\Enums\Api\ApiKeyType;
use App\Models\Passkey;
use App\Models\PersonalAccessToken;
use App\Models\Server;
use App\Models\SessionRecord;
use App\Services\Mail\MailConfigurator;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Factory as Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Drop a session's metadata row when it logs out. The Logout event fires before the session
        // is invalidated, so the id is still the one that was recorded. The event carries no request,
        // so read the session off the current one via the helper.
        Event::listen(Logout::class, function () {
            $session = session();

            if ($session->isStarted()) {
                SessionRecord::query()
                    ->where('session_id', $session->getId())
                    ->delete();
            }
        });

        $this->bootRoute();
        $this->bootRateLimits();
        $this->bootOidc();
        $this->bootMail();
    }

    /**
     * Let the panel's stored SMTP settings override MAIL_* before anything resolves a mailer.
     *
     * Hooked to the mail manager rather than run at boot, for two reasons. It is a database read,
     * and the overwhelming majority of requests never send anything — paying for it on every one
     * of them buys nothing. And resolving settings during boot pins them before anything else in
     * the process can have finished setting the database up, which is exactly how the first test
     * in a suite ended up holding values from before its own migrations ran.
     *
     * The manager reads `config('mail.mailers.*')` when a mailer is built, not when the manager
     * itself is constructed, so writing the config here lands in time.
     *
     * Deliberately swallowing everything: a panel that will not serve a request because it cannot
     * look up its own mail configuration would be a far worse failure than one that falls back to
     * the environment, which is exactly what skipping this does.
     */
    public function bootMail(): void
    {
        $this->app->afterResolving(MailManager::class, function () {
            try {
                $this->app->make(MailConfigurator::class)->apply();
            } catch (\Throwable) {
                // Environment stays in charge.
            }
        });
    }

    /**
     * Register the generic OpenID Connect Socialite driver so operators can federate against
     * any standards-compliant IdP by pointing `services.oidc.base_url` at its issuer. Socialite
     * ships no such driver, so we extend it with our own {@see OidcProvider}.
     */
    public function bootOidc(): void
    {
        $socialite = $this->app->make(Socialite::class);

        $socialite->extend('oidc', function () use ($socialite) {
            $config = config('services.oidc', []);

            $provider = $socialite->buildProvider(OidcProvider::class, $config);

            // OIDC scopes are operator-tunable (some IdPs want extra scopes to release claims),
            // but `openid` is mandatory. setScopes fully replaces the driver defaults so operators
            // retain control; we just fold `openid` back in unconditionally.
            $scopes = array_values(array_unique(array_merge(
                ['openid'],
                (array) ($config['scopes'] ?? ['profile', 'email']),
            )));

            return $provider->setScopes($scopes);
        });
    }

    /**
     * One budget per API token, across both token APIs.
     *
     * Keyed on the token rather than the address: an integration behind a NAT shares an
     * address with everything else on that network, and a leaked token used from many
     * addresses is still one budget. The panel's own browser session is not limited here;
     * the SPA polls, and its own sign-in endpoints carry the limits that protect it.
     *
     * An application token locked to an IP allowlist is not limited either. The limit is there
     * for a token that leaks, and one that only works from the integration's own hosts has
     * nowhere to leak to, while the integration it belongs to is exactly the caller a limit
     * gets in the way of. The allowlist is checked before this runs, so a request from
     * anywhere else is refused before it gets here.
     */
    public function bootRateLimits(): void
    {
        RateLimiter::for('api-tokens', function (Request $request) {
            $token = $request->user()?->currentAccessToken();
            $perMinute = (int) config('convoy.api.rate_limit', 240);

            if (! $token instanceof PersonalAccessToken || $perMinute <= 0) {
                return Limit::none();
            }

            if ($token->type === ApiKeyType::APPLICATION && ! empty($token->allowed_networks)) {
                return Limit::none();
            }

            return Limit::perMinute($perMinute)->by('token:'.$token->getKey());
        });
    }

    public function bootRoute(): void
    {
        // Passkey extends the spatie/laravel-passkeys model (required by the package's
        // config), which sidesteps Laravel's implicit route-model binding — so bind it
        // explicitly, like `server` below.
        Route::bind('passkey', fn (string $value) => Passkey::query()->findOrFail($value));

        Route::bind('server', function (string $value) {

            return Server::query()
                ->where(strlen($value) === 8 ? 'uuid_short' : 'uuid', $value)
                // Only match by id for numeric values; postgres errors casting a
                // uuid string to the bigint id column.
                ->when(is_numeric($value), fn ($query) => $query->orWhere('id', $value))
                ->firstOrFail();
        });
    }
}
