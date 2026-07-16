<?php

namespace App\Actions\Auth;

use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;

/**
 * Sends a password login through the second-factor challenge when the account
 * has either a confirmed authenticator or a user-verifying passkey.
 */
class RedirectIfSecondFactorAuthenticatable extends RedirectIfTwoFactorAuthenticatable
{
    public function handle($request, $next)
    {
        $user = $this->validateCredentials($request);

        if ($user?->hasEnabledSecondFactor()) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }
}
