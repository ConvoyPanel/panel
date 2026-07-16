<?php

namespace App\Http\Requests\Auth;

use Laravel\Fortify\Http\Requests\TwoFactorLoginRequest;

/**
 * Guards Fortify's TOTP challenge against accounts that have no TOTP.
 *
 * Since password logins started being challenged on *either* factor
 * (RedirectIfSecondFactorAuthenticatable), a passkey-only account reaches this
 * challenge with `two_factor_secret` still null. The parent's hasValidCode()
 * decrypts that column unconditionally — guarded only by `$this->code &&` — so
 * posting any code as such a user threw a DecryptException out of the request
 * and answered 500 instead of rejecting the attempt. The challenge screen hides
 * the field for them (`authenticator: false`), but the endpoint is reachable
 * regardless of what the UI offers.
 *
 * The recovery-code path is unaffected: it reads recoveryCodes(), which every
 * second factor populates.
 */
class SecondFactorLoginRequest extends TwoFactorLoginRequest
{
    public function hasValidCode()
    {
        if (blank($this->challengedUser()->two_factor_secret)) {
            return false;
        }

        return parent::hasValidCode();
    }
}
