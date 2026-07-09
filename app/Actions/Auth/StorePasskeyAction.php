<?php

namespace App\Actions\Auth;

use App\Exceptions\Http\Passkey\InvalidAuthenticatorAttestationResponse;
use App\Exceptions\Http\Passkey\InvalidPasskeyJson;
use App\Exceptions\Http\Passkey\InvalidPasskeyPublicKeyCredential;
use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction as BaseAction;
use Spatie\LaravelPasskeys\Support\Config;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Spatie\LaravelPasskeys\Support\Serializer;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

/**
 * Extends the package's store action so we inherit its package-default storage
 * (serialized {@see PublicKeyCredentialSource}), the passkey-registered event,
 * and the `additionalProperties` create path — while re-throwing Convoy's
 * curated {@see \App\Exceptions\HasErrorCode} exceptions instead of the package's
 * generic ones, so the client always gets a real status + stable error slug.
 */
class StorePasskeyAction extends BaseAction
{
    protected function determinePublicKeyCredentialSource(
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): PublicKeyCredentialSource {
        $passkeyOptions = $this->getPasskeyOptions($passkeyOptionsJson);

        $publicKeyCredential = $this->getPasskey($passkeyJson);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new InvalidPasskeyPublicKeyCredential;
        }

        $configureCeremonyStepManagerFactory = Config::getAction(
            'configure_ceremony_step_manager_factory',
            ConfigureCeremonyStepManagerFactoryAction::class,
        );
        $creationCsm = $configureCeremonyStepManagerFactory->execute()->creationCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $passkeyOptions,
                host: $hostName,
            );
        } catch (Throwable $exception) {
            throw new InvalidAuthenticatorAttestationResponse($exception);
        }

        return CredentialRecordConverter::toPublicKeyCredentialSource($publicKeyCredentialSource);
    }

    protected function getPasskeyOptions(string $passkeyOptionsJson): PublicKeyCredentialCreationOptions
    {
        if (! json_validate($passkeyOptionsJson)) {
            throw new InvalidPasskeyJson;
        }

        /** @var PublicKeyCredentialCreationOptions $passkeyOptions */
        $passkeyOptions = Serializer::make()->fromJson(
            $passkeyOptionsJson,
            PublicKeyCredentialCreationOptions::class,
        );

        return $passkeyOptions;
    }

    protected function getPasskey(string $passkeyJson): PublicKeyCredential
    {
        if (! json_validate($passkeyJson)) {
            throw new InvalidPasskeyJson;
        }

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = Serializer::make()->fromJson(
            $passkeyJson,
            PublicKeyCredential::class,
        );

        return $publicKeyCredential;
    }
}
