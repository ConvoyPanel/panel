<?php

namespace App\Providers;

use App\Actions\Auth\DisableAuthenticator;
use App\Actions\Auth\EnableAuthenticator;
use App\Actions\Auth\RedirectIfSecondFactorAuthenticatable;
use App\Http\Requests\Auth\SecondFactorLoginRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\CanonicalizeUsername;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EnableTwoFactorAuthentication::class, EnableAuthenticator::class);
        $this->app->bind(DisableTwoFactorAuthentication::class, DisableAuthenticator::class);
        // Fortify's challenge controller type-hints the parent, so the binding is
        // what gets our subclass injected. See SecondFactorLoginRequest.
        $this->app->bind(TwoFactorLoginRequest::class, SecondFactorLoginRequest::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::ignoreRoutes();

        Fortify::authenticateThrough(fn () => array_filter([
            config('fortify.limiters.login') ? null : EnsureLoginIsNotThrottled::class,
            config('fortify.lowercase_usernames') ? CanonicalizeUsername::class : null,
            Features::enabled(Features::twoFactorAuthentication())
                ? RedirectIfSecondFactorAuthenticatable::class
                : null,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(20)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(20)->by($request->session()->get('login.id'));
        });
    }
}
