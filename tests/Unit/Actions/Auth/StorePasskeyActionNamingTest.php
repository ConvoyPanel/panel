<?php

use App\Actions\Auth\StorePasskeyAction;
use App\Models\Passkey;
use App\Models\User;
use App\Support\Passkeys\AuthenticatorAaguids;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Drives the real store action but short-circuits the attestation ceremony, which needs a
 * browser-signed response we can't fabricate here. Everything after it — the naming, the create,
 * the event — is the production path.
 */
function storeActionReporting(string $aaguid): StorePasskeyAction
{
    return new class($aaguid) extends StorePasskeyAction
    {
        public function __construct(private string $aaguid) {}

        protected function determinePublicKeyCredentialSource(
            string $passkeyJson,
            string $passkeyOptionsJson,
            string $hostName,
        ): PublicKeyCredentialSource {
            return CredentialRecordConverter::toPublicKeyCredentialSource(CredentialRecord::create(
                publicKeyCredentialId: random_bytes(16),
                type: 'public-key',
                transports: ['internal'],
                attestationType: 'none',
                trustPath: new EmptyTrustPath,
                aaguid: Uuid::fromString($this->aaguid),
                credentialPublicKey: 'pubkey-bytes',
                userHandle: 'user-handle',
                counter: 0,
            ));
        }
    };
}

function registerPasskey(HasPasskeys $user, string $aaguid): string
{
    return storeActionReporting($aaguid)
        ->execute($user, '{}', '{}', 'convoy.test')
        ->name;
}

it('names a passkey after the authenticator that created it', function (string $aaguid, string $expected) {
    $user = User::factory()->create();

    expect(registerPasskey($user, $aaguid))->toBe($expected);
})->with([
    '1Password' => ['bada5566-a7aa-401f-bd96-45619a55120d', '1Password'],
    'Bitwarden' => ['d548826e-79b4-db40-a3d8-11116f7e8349', 'Bitwarden'],
    'Apple Passwords' => ['fbfc3007-154e-4ecc-8c0b-6e020557d7bd', 'Apple Passwords'],
    'Windows Hello' => ['08987058-cadc-4b81-b6e1-30de50dcbe96', 'Windows Hello'],
    'YubiKey' => ['cb69481e-8ff7-4039-93ec-0a2729a154a8', 'YubiKey 5 Series'],
]);

it('falls back to a datestamp when the authenticator is unidentifiable', function (string $aaguid) {
    $user = User::factory()->create();

    expect(registerPasskey($user, $aaguid))->toBe('Passkey '.now()->format('Y-m-d'));
})->with([
    // Some browsers substitute an all-zero AAGUID rather than identify the authenticator.
    'zeroed' => ['00000000-0000-0000-0000-000000000000'],
    'absent from the snapshot' => ['3a1b7c9d-0000-4000-8000-abcdefabcdef'],
]);

it('suffixes repeat registrations from the same authenticator', function () {
    $user = User::factory()->create();
    $onePassword = 'bada5566-a7aa-401f-bd96-45619a55120d';

    expect(registerPasskey($user, $onePassword))->toBe('1Password')
        ->and(registerPasskey($user, $onePassword))->toBe('1Password (2)')
        ->and(registerPasskey($user, $onePassword))->toBe('1Password (3)');
});

it('lets the caller override the derived name', function () {
    $user = User::factory()->create();

    $passkey = storeActionReporting('bada5566-a7aa-401f-bd96-45619a55120d')
        ->execute($user, '{}', '{}', 'convoy.test', ['name' => 'Work laptop']);

    expect($passkey->name)->toBe('Work laptop');
});

it('keeps every mapped name within the length a rename would accept', function () {
    expect(AuthenticatorAaguids::names())->not->toBeEmpty();

    foreach (AuthenticatorAaguids::names() as $aaguid => $name) {
        // AAGUIDs only look like UUIDs — they're 16 opaque bytes, so several in the table are not
        // valid RFC 4122 (Proton Pass's is the ASCII "ProtonPassProton"). Only the shape and the
        // lowercasing self::nameFor() looks up by are guaranteed.
        expect($aaguid)->toMatch('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/')
            ->and(mb_strlen($name))->toBeGreaterThan(0)->toBeLessThanOrEqual(Passkey::NAME_MAX_LENGTH);
    }
});
