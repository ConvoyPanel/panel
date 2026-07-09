<?php

namespace App\Actions\Auth;

use App\Models\Passkey;
use App\Services\Auth\PasskeySerializer;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

use function app;
use function config;

class FindPasskeyToAuthenticateAction
{
    public function execute(
        string $publicKeyCredentialJson,
        string $passkeyOptionsJson,
    ): ?Passkey {
        $publicKeyCredential = $this->determinePublicKeyCredential($publicKeyCredentialJson);

        if (! $publicKeyCredential) {
            return null;
        }

        $passkey = $this->findPasskey($publicKeyCredential);

        if (! $passkey) {
            return null;
        }

        /** @var PublicKeyCredentialRequestOptions $passkeyOptions */
        $passkeyOptions = PasskeySerializer::make()->fromJson(
            $passkeyOptionsJson,
            PublicKeyCredentialRequestOptions::class,
        );

        $credentialRecord = $this->determineCredentialRecord(
            $publicKeyCredential,
            $passkeyOptions,
            $passkey,
        );

        if (! $credentialRecord) {
            return null;
        }

        $this->updatePasskey($passkey, $credentialRecord);

        return $passkey;
    }

    public function determinePublicKeyCredential(
        string $publicKeyCredentialJson,
    ): ?PublicKeyCredential {
        $publicKeyCredential = PasskeySerializer::make()->fromJson(
            $publicKeyCredentialJson,
            PublicKeyCredential::class,
        );

        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return null;
        }

        return $publicKeyCredential;
    }

    protected function findPasskey(PublicKeyCredential $publicKeyCredential): ?Passkey
    {
        $credentialId = Base64UrlSafe::encodeUnpadded($publicKeyCredential->rawId);

        return Passkey::firstWhere('credential_id', $credentialId);
    }

    protected function determineCredentialRecord(
        PublicKeyCredential $publicKeyCredential,
        PublicKeyCredentialRequestOptions $passkeyOptions,
        Passkey $passkey,
    ): ?CredentialRecord {
        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            return null;
        }

        $csmFactory = new CeremonyStepManagerFactory;
        if (app()->environment('local') && config('app.version') === 'canary') {
            $csmFactory->setAllowedOrigins(['localhost']);
        }
        $requestCsm = $csmFactory->requestCeremony();

        try {
            $validator = AuthenticatorAssertionResponseValidator::create($requestCsm);

            return $validator->check(
                credentialRecord: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: $passkeyOptions,
                host: parse_url(config('app.url'), PHP_URL_HOST),
                userHandle: null,
            );
        } catch (Throwable) {
            return null;
        }
    }

    protected function updatePasskey(
        Passkey $passkey,
        CredentialRecord $credentialRecord
    ): self {
        $passkey->update([
            'data' => $credentialRecord,
            'last_used_at' => now(),
        ]);

        return $this;
    }
}
