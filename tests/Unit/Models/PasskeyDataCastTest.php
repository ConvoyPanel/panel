<?php

use App\Models\Passkey;
use App\Services\Auth\PasskeySerializer;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

function makeCredentialRecord(int $counter = 7): CredentialRecord
{
    return CredentialRecord::create(
        publicKeyCredentialId: 'cred-id-bytes',
        type: 'public-key',
        transports: ['internal'],
        attestationType: 'none',
        trustPath: new EmptyTrustPath,
        aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
        credentialPublicKey: 'pubkey-bytes',
        userHandle: 'user-handle',
        counter: $counter,
    );
}

it('stores a CredentialRecord and derives credential_id on write', function () {
    $record = makeCredentialRecord();

    $passkey = new Passkey;
    $passkey->data = $record;

    expect($passkey->credential_id)
        ->toBe(Base64UrlSafe::encodeUnpadded($record->publicKeyCredentialId))
        ->and($passkey->getAttributes()['data'])->toBeString()->toContain('"counter":7');
});

it('rehydrates data as a base CredentialRecord, not the deprecated PublicKeyCredentialSource', function () {
    $passkey = new Passkey;
    $passkey->data = makeCredentialRecord(counter: 12);

    $hydrated = $passkey->data;

    expect($hydrated)
        ->toBeInstanceOf(CredentialRecord::class)
        ->not->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->and($hydrated->counter)->toBe(12)
        ->and($hydrated->publicKeyCredentialId)->toBe('cred-id-bytes');
});

it('rehydrates legacy PublicKeyCredentialSource-serialized rows as a CredentialRecord', function () {
    // Rows written before the migration were serialized from a PublicKeyCredentialSource.
    // Their JSON is byte-identical to a CredentialRecord's, so they must load transparently.
    $legacy = PublicKeyCredentialSource::create(
        publicKeyCredentialId: 'cred-id-bytes',
        type: 'public-key',
        transports: ['internal'],
        attestationType: 'none',
        trustPath: new EmptyTrustPath,
        aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
        credentialPublicKey: 'pubkey-bytes',
        userHandle: 'user-handle',
        counter: 3,
    );
    $legacyJson = PasskeySerializer::make()->toJson($legacy);

    $passkey = new Passkey;
    $passkey->setRawAttributes(['data' => $legacyJson]);

    expect($passkey->data)
        ->toBeInstanceOf(CredentialRecord::class)
        ->not->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->and($passkey->data->counter)->toBe(3);
});
