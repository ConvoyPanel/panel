<?php

namespace App\Actions\Auth;

use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;

/** Keep account-level recovery codes while a passkey remains enabled. */
class DisableAuthenticator extends DisableTwoFactorAuthentication
{
    public function __invoke($user)
    {
        if (is_null($user->two_factor_secret) && is_null($user->two_factor_confirmed_at)) {
            return;
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => $user->passkeys()->exists()
                ? $user->two_factor_recovery_codes
                : null,
        ])->save();

        TwoFactorAuthenticationDisabled::dispatch($user);
    }
}
