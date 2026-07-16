<?php

use App\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

/**
 * Two factor is only real once the user has proved they scanned the secret.
 *
 * `enable` mints the secret the moment the setup dialog opens, so "has a secret"
 * and "has working two factor" are different states. Reporting the former as
 * enabled meant an abandoned setup claimed to protect an account it did not, and
 * disagreed with the login challenge — which asks Fortify, not the column.
 */
function enableTwoFactorFor(User $user, bool $confirmed): string
{
    $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    // encrypt(), not encryptString(): Fortify reads these back with decrypt(),
    // which unserializes, so an unserialized payload blows up inside the action.
    $user->forceFill([
        'two_factor_secret' => encrypt($secret),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-one'])),
        'two_factor_confirmed_at' => $confirmed ? now() : null,
    ])->save();

    return $secret;
}

it('does not report an unconfirmed secret as enabled', function () {
    $user = User::factory()->create();
    enableTwoFactorFor($user, confirmed: false);

    $this->actingAs($user)
        ->getJson('/api/client/account/authenticator/status')
        ->assertSuccessful()
        ->assertExactJson(['enabled' => false]);

    expect($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('reports a confirmed secret as enabled', function () {
    $user = User::factory()->create();
    enableTwoFactorFor($user, confirmed: true);

    $this->actingAs($user)
        ->getJson('/api/client/account/authenticator/status')
        ->assertSuccessful()
        ->assertExactJson(['enabled' => true]);
});

it('keeps authenticator status method-specific for a passkey-only account', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);

    $this->actingAs($user)
        ->getJson('/api/client/account/authenticator/status')
        ->assertSuccessful()
        ->assertExactJson(['enabled' => false]);

    expect($user->fresh()->hasEnabledSecondFactor())->toBeTrue();
});

it('enables two factor once a generated code is confirmed', function () {
    $user = User::factory()->create();
    $secret = enableTwoFactorFor($user, confirmed: false);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson('/api/client/account/authenticator/confirm', [
            'code' => (new Google2FA)->getCurrentOtp($secret),
        ])
        ->assertSuccessful();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull()
        ->and($user->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('rejects a wrong code and leaves two factor unconfirmed', function () {
    $user = User::factory()->create();
    enableTwoFactorFor($user, confirmed: false);

    $this->actingAs($user)
        ->withSession(confirmedSession())
        ->postJson('/api/client/account/authenticator/confirm', ['code' => '000000'])
        ->assertStatus(422);

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('refuses to confirm without a confirmed identity', function () {
    $user = User::factory()->create();
    $secret = enableTwoFactorFor($user, confirmed: false);

    $this->actingAs($user)
        ->postJson('/api/client/account/authenticator/confirm', [
            'code' => (new Google2FA)->getCurrentOtp($secret),
        ])
        ->assertForbidden();

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});
