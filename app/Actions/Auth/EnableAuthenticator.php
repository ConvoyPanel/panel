<?php

namespace App\Actions\Auth;

use Illuminate\Support\Collection;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;
use Laravel\Fortify\TwoFactorAuthenticationProvider;

/** Keep account-level recovery codes when adding an authenticator to a passkey. */
class EnableAuthenticator extends EnableTwoFactorAuthentication
{
    public function __invoke($user, $force = false)
    {
        if (empty($user->two_factor_secret) || $force === true) {
            // Fortify's contract still declares this method without arguments,
            // while its concrete provider accepts the configured secret length
            // and Fortify's own action passes it. The application binding is
            // that concrete provider; make the current package contract explicit
            // until its interface catches up with its implementation.
            /** @var TwoFactorAuthenticationProvider $provider */
            $provider = $this->provider;

            $attributes = [
                'two_factor_secret' => Fortify::currentEncrypter()->encrypt(
                    $provider->generateSecretKey(
                        (int) config('fortify-options.two-factor-authentication.secret-length', 16),
                    ),
                ),
            ];

            if (empty($user->two_factor_recovery_codes)) {
                $attributes['two_factor_recovery_codes'] = Fortify::currentEncrypter()->encrypt(
                    json_encode(Collection::times(8, fn () => RecoveryCode::generate())->all()),
                );
            }

            $user->forceFill($attributes)->save();

            TwoFactorAuthenticationEnabled::dispatch($user);
        }
    }
}
