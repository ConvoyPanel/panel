<?php

use App\Models\Passkey;
use App\Models\User;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Persist a valid passkey for a user through the model's data cast (mirrors what
 * StorePasskeyAction does). Because App\Models\Passkey extends the spatie model,
 * Laravel's implicit route-model binding does NOT resolve {passkey}; an explicit
 * Route::bind('passkey', …) in AppServiceProvider covers it. These tests guard
 * that binding (and the ownership policy) so the regression can't return silently.
 */
function makeStoredPasskey(User $user, string $name = 'Test Key'): Passkey
{
    $source = CredentialRecordConverter::toPublicKeyCredentialSource(CredentialRecord::create(
        publicKeyCredentialId: random_bytes(16),
        type: 'public-key',
        transports: ['internal'],
        attestationType: 'none',
        trustPath: new EmptyTrustPath,
        aaguid: Uuid::fromString('00000000-0000-0000-0000-000000000000'),
        credentialPublicKey: 'pubkey-bytes',
        userHandle: $user->uuid,
        counter: 0,
    ));

    $passkey = new Passkey(['name' => $name]);
    $passkey->data = $source; // cast fills credential_id + serialized data
    $user->passkeys()->save($passkey);

    return $passkey;
}

/** RequireIdentityConfirmation gates the passkey routes (300s window). */
function confirmedSession(): array
{
    return ['auth.identity_confirmed_at' => now()->timestamp];
}

it('resolves the {passkey} route binding and renames the passkey', function () {
    $user = User::factory()->create();
    $passkey = makeStoredPasskey($user);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson("/api/client/account/passkeys/{$passkey->id}/rename", ['name' => 'Renamed'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Renamed');

    expect($passkey->refresh()->name)->toBe('Renamed');
});

it('resolves the {passkey} route binding and deletes the passkey', function () {
    $user = User::factory()->create();
    $passkey = makeStoredPasskey($user);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->deleteJson("/api/client/account/passkeys/{$passkey->id}")
        ->assertNoContent();

    expect(Passkey::query()->whereKey($passkey->id)->exists())->toBeFalse();
});

it('forbids managing another user\'s passkey', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $passkey = makeStoredPasskey($other);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson("/api/client/account/passkeys/{$passkey->id}/rename", ['name' => 'X'])
        ->assertNotFound();

    expect($passkey->refresh()->name)->toBe('Test Key');
});
