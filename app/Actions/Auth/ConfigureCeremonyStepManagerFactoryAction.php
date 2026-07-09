<?php

namespace App\Actions\Auth;

use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction as BaseAction;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * Folds Convoy's `canary`/`localhost` origin handling into the package's single
 * ceremony-configuration hook. On a local canary build we allow `localhost` as a
 * secured relying-party id (registration) and an allowed origin (authentication),
 * so passkeys work over http://localhost during development.
 */
class ConfigureCeremonyStepManagerFactoryAction extends BaseAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $csmFactory = parent::execute();

        if (app()->environment('local') && config('app.version') === 'canary') {
            $csmFactory->setSecuredRelyingPartyId(['localhost']);
            $csmFactory->setAllowedOrigins(['localhost']);
        }

        return $csmFactory;
    }
}
