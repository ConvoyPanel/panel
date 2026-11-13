<?php

namespace Convoy\Providers;

use Convoy\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Convoy\Actions\Fortify\CreateNewUser;
use Illuminate\Support\Facades\RateLimiter;
use Convoy\Actions\Fortify\ResetUserPassword;
use Convoy\Actions\Fortify\UpdateUserPassword;
use Convoy\Actions\Fortify\UpdateUserProfileInformation;

class FortifyServiceProvider extends ServiceProvider
{
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        //Fortify::ignoreRoutes();

        // Signing in with "Bob@Example.com" found bob@example.com on MySQL, whose
        // collation ignores case. Postgres compares exactly, so look the account up
        // the way MySQL did: an exact match first, then one ignoring case.
        Fortify::authenticateUsing(function (Request $request) {
            $email = (string) $request->input(Fortify::username());

            $user = User::query()->where('email', $email)->first()
                ?? User::query()->whereRaw('LOWER(email) = ?', [Str::lower($email)])->orderBy('id')->first();

            if ($user && Hash::check((string) $request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(20)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(20)->by($request->session()->get('login.id'));
        });
    }
}
