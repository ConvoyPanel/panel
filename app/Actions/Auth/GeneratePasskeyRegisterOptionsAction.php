<?php

namespace App\Actions\Auth;

use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction as BaseAction;
use Webauthn\AuthenticatorSelectionCriteria;

class GeneratePasskeyRegisterOptionsAction extends BaseAction
{
    public function authenticatorSelection(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
        );
    }
}
