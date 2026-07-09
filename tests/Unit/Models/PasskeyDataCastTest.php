<?php

use App\Models\Passkey;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Spatie\LaravelPasskeys\Support\Serializer;
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

function makePublicKeyCredentialSource(int $counter = 7): PublicKeyCredentialSource
{
    return CredentialRecordConverter::toPublicKeyCredentialSource(makeCredentialRecord($counter));
}

it('stores a PublicKeyCredentialSource and derives credential_id on write', function () {
    $source = makePublicKeyCredentialSource();

    $passkey = new Passkey;
    $passkey->data = $source;

    // The spatie model encodes the credential id in a DB-safe way (base64 on
    // Postgres) via Passkey::encodeCredentialId().
    expect($passkey->credential_id)
        ->toBe(Passkey::encodeCredentialId($source->publicKeyCredentialId))
        ->and($passkey->getAttributes()['data'])->toBeString()->toContain('"counter":7');
});

it('rehydrates data as a PublicKeyCredentialSource', function () {
    $passkey = new Passkey;
    $passkey->data = makePublicKeyCredentialSource(counter: 12);

    $hydrated = $passkey->data;

    expect($hydrated)
        ->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->and($hydrated->counter)->toBe(12)
        ->and($hydrated->publicKeyCredentialId)->toBe('cred-id-bytes');
});

it('rehydrates legacy CredentialRecord-serialized rows as a PublicKeyCredentialSource', function () {
    // The interim hand-rolled path (before adopting spatie/laravel-passkeys)
    // serialized rows from a base CredentialRecord. Its JSON is byte-identical
    // to a PublicKeyCredentialSource's, so those rows must load transparently.
    $legacyJson = Serializer::make()->toJson(makeCredentialRecord(counter: 3));

    $passkey = new Passkey;
    $passkey->setRawAttributes(['data' => $legacyJson]);

    expect($passkey->data)
        ->toBeInstanceOf(PublicKeyCredentialSource::class)
        ->and($passkey->data->counter)->toBe(3);
});
