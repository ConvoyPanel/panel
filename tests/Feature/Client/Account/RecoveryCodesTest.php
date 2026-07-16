<?php

use App\Actions\Auth\DisableAuthenticator;
use App\Actions\Auth\EnableAuthenticator;
use App\Models\User;
use Laravel\Fortify\Fortify;

it('returns account recovery codes without requiring an authenticator secret', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['passkey-code'])),
    ])->save();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->getJson('/api/client/account/authenticator/recovery-codes')
        ->assertExactJson(['passkey-code']);
});

it('preserves existing recovery codes when adding an authenticator', function () {
    $user = User::factory()->create();
    $encryptedCodes = Fortify::currentEncrypter()->encrypt(json_encode(['existing-code']));
    $user->forceFill(['two_factor_recovery_codes' => $encryptedCodes])->save();

    app(EnableAuthenticator::class)($user);

    expect($user->fresh()->two_factor_recovery_codes)->toBe($encryptedCodes);
});

it('preserves recovery codes when disabling an authenticator while a passkey remains', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);
    $encryptedCodes = Fortify::currentEncrypter()->encrypt(json_encode(['existing-code']));
    $user->forceFill([
        'two_factor_secret' => Fortify::currentEncrypter()->encrypt('secret'),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => $encryptedCodes,
    ])->save();

    app(DisableAuthenticator::class)($user);

    expect($user->fresh())
        ->two_factor_secret->toBeNull()
        ->two_factor_confirmed_at->toBeNull()
        ->two_factor_recovery_codes->toBe($encryptedCodes);
});

it('clears recovery codes with the last second factor so a future passkey gets fresh codes', function () {
    $user = User::factory()->create();
    $passkey = secondFactorPasskey($user);
    $user->forceFill([
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['old-code'])),
    ])->save();

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->deleteJson("/api/client/account/passkeys/{$passkey->id}")
        ->assertNoContent();

    expect($user->fresh()->two_factor_recovery_codes)->toBeNull();
});

it('backfills recovery codes for existing passkey-only accounts', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);

    $migration = require database_path(
        'migrations/2026_07_15_000003_issue_recovery_codes_to_passkey_users.php',
    );
    $migration->up();

    $codes = json_decode(Fortify::currentEncrypter()->decrypt(
        $user->fresh()->two_factor_recovery_codes,
    ), true);

    expect($codes)->toHaveCount(8)->each->toBeString();
});
