<?php

namespace App\Actions\Auth;

use App\Exceptions\Http\Passkey\InvalidAuthenticatorAttestationResponse;
use App\Exceptions\Http\Passkey\InvalidPasskeyJson;
use App\Exceptions\Http\Passkey\InvalidPasskeyPublicKeyCredential;
use App\Models\Passkey;
use App\Models\User;
use App\Services\Auth\PasskeySerializer;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

use function app;
use function config;

class StorePasskeyAction
{
    public function execute(
        User $user,
        string $name,
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): Passkey {
        $credentialRecord = $this->determineCredentialRecord(
            $passkeyJson,
            $passkeyOptionsJson,
            $hostName
        );

        return $user->passkeys()->create([
            'name' => $name,
            'data' => $credentialRecord,
        ]);
    }

    protected function determineCredentialRecord(
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): CredentialRecord {
        $passkeyOptions = $this->getPasskeyOptions($passkeyOptionsJson);

        $publicKeyCredential = $this->getPasskey($passkeyJson);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new InvalidPasskeyPublicKeyCredential;
        }

        $csmFactory = new CeremonyStepManagerFactory;
        if (app()->environment('local') && config('app.version') === 'canary') {
            $csmFactory->setSecuredRelyingPartyId(['localhost']);
        }
        $creationCsm = $csmFactory->creationCeremony();

        try {
            return AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $passkeyOptions,
                host: $hostName,
            );
        } catch (Throwable $exception) {
            throw new InvalidAuthenticatorAttestationResponse($exception);
        }
    }

    protected function getPasskeyOptions(string $passkeyOptionsJson): PublicKeyCredentialCreationOptions
    {
        if (! json_validate($passkeyOptionsJson)) {
            throw new InvalidPasskeyJson;
        }

        /** @var PublicKeyCredentialCreationOptions $passkeyOptions */
        $passkeyOptions = PasskeySerializer::make()->fromJson(
            $passkeyOptionsJson,
            PublicKeyCredentialCreationOptions::class
        );

        return $passkeyOptions;
    }

    protected function getPasskey(string $passkeyJson): PublicKeyCredential
    {
        if (! json_validate($passkeyJson)) {
            throw new InvalidPasskeyJson;
        }

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = PasskeySerializer::make()->fromJson(
            $passkeyJson,
            PublicKeyCredential::class
        );

        return $publicKeyCredential;
    }
}
