<?php

use App\Models\User;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;

/**
 * A WebAuthn challenge is single use. Both of these flows used to `get` their
 * options out of the session instead of `pull`ing them, so the challenge stayed
 * valid indefinitely and a captured assertion remained replayable against the
 * same session. The second-factor challenge already did this correctly; these
 * two pre-dated it.
 *
 * The mocks assert `->once()`: if the guard did not fire before reaching the
 * action, the replay would call it a second time and the expectation would fail.
 */
it('consumes the passkey login challenge so an assertion cannot be replayed', function () {
    $user = User::factory()->create();
    $passkey = secondFactorPasskey($user);

    $this->mock(FindPasskeyToAuthenticateAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn($passkey);

    $this->withSession(['passkeys.authentication-options' => '{}'])
        ->postJson('/api/auth/passkeys/verify-authentication', ['id' => 'credential'])
        ->assertNoContent();

    $this->assertAuthenticatedAs($user);

    auth()->logout();

    $this->postJson('/api/auth/passkeys/verify-authentication', ['id' => 'credential'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');

    $this->assertGuest();
});

it('rejects a passkey login verify that was never issued a challenge', function () {
    // execute() types its options non-nullable, so a missing challenge used to
    // be a TypeError and a 500 rather than a rejected attempt.
    $this->postJson('/api/auth/passkeys/verify-authentication', ['id' => 'credential'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');

    $this->assertGuest();
});

it('consumes the identity confirmation challenge so presence cannot be replayed', function () {
    $user = User::factory()->create();
    $passkey = secondFactorPasskey($user);

    $this->mock(FindPasskeyToAuthenticateAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturn($passkey);

    $this->actingAs($user)
        ->withSession(['passkeys.identity-options' => '{}'])
        ->postJson('/api/auth/identity/confirm', ['passkey' => '{"id":"credential"}'])
        ->assertSuccessful()
        ->assertJson(['confirmed' => true])
        ->assertSessionHas('auth.identity_confirmed_at');

    // Same assertion, second time: the challenge is gone, so re-confirming
    // presence with a replay is refused.
    $this->actingAs($user)
        ->postJson('/api/auth/identity/confirm', ['passkey' => '{"id":"credential"}'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');
});

it('rejects an identity confirmation that was never issued a challenge', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);

    $this->actingAs($user)
        ->postJson('/api/auth/identity/confirm', ['passkey' => '{"id":"credential"}'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');
});

/**
 * The two flows shared one session key. They are separate ceremonies with
 * separate meanings — one logs you in, one proves you are still at the keyboard —
 * so a challenge minted for one must not satisfy the other.
 */
it('does not let a login challenge satisfy an identity confirmation', function () {
    $user = User::factory()->create();
    secondFactorPasskey($user);

    $this->actingAs($user)
        ->withSession(['passkeys.authentication-options' => '{}'])
        ->postJson('/api/auth/identity/confirm', ['passkey' => '{"id":"credential"}'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');
});

/**
 * The client used to keep its own copy of the confirmation window and decide the
 * gate for itself. It asks now, so the answer has to come from the same place
 * RequireIdentityConfirmation reads — one fact, one owner.
 */
it('reports identity as unconfirmed with no confirmation on the session', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/auth/identity')
        ->assertExactJson(['confirmed' => false, 'expires_in' => null]);
});

it('reports identity as confirmed with the time remaining', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['auth.identity_confirmed_at' => now()->subSeconds(60)->timestamp])
        ->getJson('/api/auth/identity')
        ->assertSuccessful()
        ->assertJson(['confirmed' => true, 'expires_in' => 240]);
});

it('agrees with the middleware at the edge of the window', function () {
    $user = User::factory()->create();

    // One second inside: reported confirmed, and the gated route lets it through.
    $this->actingAs($user)
        ->withSession(['auth.identity_confirmed_at' => now()->subSeconds(299)->timestamp])
        ->getJson('/api/auth/identity')
        ->assertJson(['confirmed' => true, 'expires_in' => 1]);

    $this->actingAs($user)
        ->withSession(['auth.identity_confirmed_at' => now()->subSeconds(299)->timestamp])
        ->getJson('/api/client/account/recovery-codes')
        ->assertSuccessful();

    // One second past: reported unconfirmed, and the gated route refuses.
    $this->actingAs($user)
        ->withSession(['auth.identity_confirmed_at' => now()->subSeconds(301)->timestamp])
        ->getJson('/api/auth/identity')
        ->assertJson(['confirmed' => false, 'expires_in' => null]);

    $this->actingAs($user)
        ->withSession(['auth.identity_confirmed_at' => now()->subSeconds(301)->timestamp])
        ->getJson('/api/client/account/recovery-codes')
        ->assertForbidden();
});
