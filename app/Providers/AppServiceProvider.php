<?php

namespace App\Providers;

use App\Models\Passkey;
use App\Models\PersonalAccessToken;
use App\Models\Server;
use App\Models\SessionRecord;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
