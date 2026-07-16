<?php

namespace App\Actions\Auth;

use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction as BaseAction;
use Spatie\LaravelPasskeys\Support\Config;
use Spatie\LaravelPasskeys\Support\Serializer;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Requires user verification for every authentication ceremony.
 *
 * Without it webauthn-lib defaults to `preferred`: the authenticator *should*
 * verify, but a UV=false assertion is accepted anyway. Every platform
 * authenticator does UV regardless, which is what makes this invisible — the
 * threat is one that does not (a scripted authenticator, a key with PIN off)
 * replaying a stolen credential. A passkey only counts as two factors because
 * of this line, and the login flow skips the second-factor challenge on that
 * basis.
 *
 * Unlike the base action this does NOT stash the options in the session: it has
 * three callers (guest login, the second-factor challenge, identity
 * confirmation) whose ceremonies must not share a challenge, so each owns its
 * own key and consumes it with pull(). The base's single global key is only read
 * by the package's own controller, which we do not route.
 */
class GeneratePasskeyAuthenticationOptionsAction extends BaseAction
{
    public function execute(): string
    {
        $options = new PublicKeyCredentialRequestOptions(
            challenge: Str::random(),
            rpId: Config::getRelyingPartyId(),
            allowCredentials: [],
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );

        return Serializer::make()->toJson($options);
    }
}
