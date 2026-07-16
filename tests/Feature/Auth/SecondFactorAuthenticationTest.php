<?php

use App\Actions\Auth\StorePasskeyAction;
use App\Models\User;
use Laravel\Fortify\Fortify;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;

it('challenges a password login when the account has a passkey', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertSuccessful()
        ->assertExactJson(['two_factor' => true])
        ->assertSessionHas('login.id', $user->id);

    $this->assertGuest();
});

it('logs a password-only account in without a challenge', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSuccessful();

    $this->assertAuthenticatedAs($user);
});

it('reports the methods belonging to the pending login', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);
    $user->forceFill([
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['one-code'])),
    ])->save();

    $this->withSession(['login.id' => $user->id])
        ->getJson('/api/auth/second-factor')
        ->assertExactJson([
            'authenticator' => false,
            'passkey' => true,
            'recovery' => true,
        ]);
});

it('logs in only when the asserted passkey belongs to the pending user', function () {
    $user = User::factory()->create();
    $passkey = secondFactorPasskey($user);

    $this->mock(FindPasskeyToAuthenticateAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn($passkey);

    $this->withSession([
        'login.id' => $user->id,
        'login.remember' => false,
        'passkeys.second-factor-options' => '{}',
    ])->postJson('/api/auth/second-factor/verify-passkey', ['id' => 'credential'])
        ->assertNoContent()
        ->assertSessionMissing('login.id');

    $this->assertAuthenticatedAs($user);
});

it('rejects a valid passkey belonging to another account', function () {
    $pendingUser = User::factory()->create();
    $otherPasskey = secondFactorPasskey(User::factory()->create());

    $this->mock(FindPasskeyToAuthenticateAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn($otherPasskey);

    $this->withSession([
        'login.id' => $pendingUser->id,
        'passkeys.second-factor-options' => '{}',
    ])->postJson('/api/auth/second-factor/verify-passkey', ['id' => 'credential'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');

    $this->assertGuest();
});

/**
 * A passkey-only account now reaches the TOTP challenge (a password login is
 * challenged on either factor), but has no `two_factor_secret`. Fortify's
 * request decrypts that column whenever a `code` is present, so this posted
 * straight through a DecryptException and 500'd before the guard existed.
 */
it('rejects a code on the authenticator challenge for a passkey-only account', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);
    $user->forceFill([
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
    ])->save();

    $this->withSession(['login.id' => $user->id])
        ->postJson('/api/auth/authenticator/verify-challenge', ['code' => '123456'])
        ->assertStatus(422);

    $this->assertGuest();
});

it('still accepts a recovery code on the challenge for a passkey-only account', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);
    $user->forceFill([
        'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-code'])),
    ])->save();

    $this->withSession(['login.id' => $user->id])
        ->postJson('/api/auth/authenticator/verify-challenge', ['recovery_code' => 'recovery-code'])
        ->assertSuccessful();

    $this->assertAuthenticatedAs($user);
});

it('issues recovery codes with the first passkey', function () {
    $user = User::factory()->create();
    $passkey = secondFactorPasskey($user);

    $this->mock(StorePasskeyAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn($passkey);

    $response = $this->actingAs($user)
        ->withSession(confirmedSession() + [
            'passkeys.registration-options' => '{}',
        ])
        ->postJson('/api/client/account/passkeys/verify-registration', ['id' => 'credential'])
        ->assertSuccessful()
        ->assertJsonCount(8, 'recovery_codes');

    expect($response->json('recovery_codes'))->each->toBeString()
        ->and($user->fresh()->two_factor_recovery_codes)->not->toBeNull();
});
