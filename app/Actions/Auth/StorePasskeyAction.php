<?php

namespace App\Actions\Auth;

use App\Exceptions\HasErrorCode;
use App\Exceptions\Http\Passkey\InvalidAuthenticatorAttestationResponse;
use App\Exceptions\Http\Passkey\InvalidPasskeyJson;
use App\Exceptions\Http\Passkey\InvalidPasskeyPublicKeyCredential;
use App\Models\Passkey;
use App\Support\Passkeys\AuthenticatorAaguids;
use Illuminate\Support\Str;
use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction as BaseAction;
use Spatie\LaravelPasskeys\Events\PasskeyRegisteredEvent;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
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
 * curated {@see HasErrorCode} exceptions instead of the package's
 * generic ones, so the client always gets a real status + stable error slug,
 * and naming the passkey after the authenticator that created it
 * (see {@see self::defaultName()}).
 */
class StorePasskeyAction extends BaseAction
{
    /**
     * Mirrors the package's execute(), with one addition: the passkey is named after the
     * authenticator that created it. The name has to be decided here rather than by the caller
     * because the AAGUID only becomes known once the attestation response has been validated.
     */
    public function execute(
        HasPasskeys $authenticatable,
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
        array $additionalProperties = [],
    ): Passkey {
        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $passkeyJson,
            $passkeyOptionsJson,
            $hostName,
        );

        /** @var Passkey $passkey */
        $passkey = $authenticatable->passkeys()->create([
            'name' => $this->defaultName($authenticatable, $publicKeyCredentialSource),
            ...$additionalProperties,
            'data' => $publicKeyCredentialSource,
        ]);

        event(new PasskeyRegisteredEvent($passkey, $authenticatable));

        return $passkey;
    }

    /**
     * Name the passkey after whatever created it — "1Password", "iCloud Keychain", "YubiKey 5
     * Series" — so the account settings list reads sensibly even if the user never renames it.
     * Authenticators we can't identify (unknown or all-zero AAGUID) fall back to a datestamp.
     *
     * Registering a second passkey from the same authenticator gets a counter suffix, since the
     * name is all the list has to tell two entries apart.
     */
    protected function defaultName(
        HasPasskeys $authenticatable,
        PublicKeyCredentialSource $publicKeyCredentialSource,
    ): string {
        $authenticator = AuthenticatorAaguids::nameFor($publicKeyCredentialSource->aaguid);

        if ($authenticator === null) {
            return 'Passkey '.now()->format('Y-m-d');
        }

        $taken = $authenticatable->passkeys()->pluck('name')->all();
        $name = $authenticator;

        for ($suffix = 2; in_array($name, $taken, true); $suffix++) {
            $name = Str::limit($authenticator, Passkey::NAME_MAX_LENGTH - strlen(" ($suffix)"), '')." ($suffix)";
        }

        return $name;
    }

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
