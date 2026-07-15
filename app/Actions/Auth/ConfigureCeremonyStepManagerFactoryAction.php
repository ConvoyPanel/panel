<?php

namespace App\Actions\Auth;

use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction as BaseAction;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * Folds Convoy's `canary`/`localhost` origin handling into the package's single
 * ceremony-configuration hook, so passkeys work over http://localhost during
 * development.
 *
 * Only `setSecuredRelyingPartyId` is needed for that, and it is deliberately the
 * ONLY thing set here. `setAllowedOrigins` is a different mechanism, not a
 * companion to it: CheckAllowedOrigins treats a non-empty list as an exhaustive
 * allowlist and stops matching the origin against the relying-party id at all.
 * Passing `['localhost']` therefore made `https://localhost` the only origin the
 * ceremony would accept on any local canary build — every registration from the
 * real dev URL failed with "Invalid origin. Not in the list of allowed origins."
 * and the passkey never reached the database. Leaving the list empty keeps the
 * default rp-id check, which accepts any origin whose host matches the relying
 * party (dev and production alike), while securedRelyingPartyId exempts
 * localhost from the HTTPS requirement — the actual reason this hook exists.
 */
class ConfigureCeremonyStepManagerFactoryAction extends BaseAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $csmFactory = parent::execute();

        if (app()->environment('local') && config('app.version') === 'canary') {
            $csmFactory->setSecuredRelyingPartyId(['localhost']);
        }

        return $csmFactory;
    }
}
