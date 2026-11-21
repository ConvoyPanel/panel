<?php

use App\Actions\Auth\StorePasskeyAction;
use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserInvite;
use App\Services\Users\UserInviteService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

/**
 * Accepting an invite with a passkey instead of a password.
 *
 * The attestation itself is the package's to verify, so StorePasskeyAction is mocked: these
 * tests are about what the invite flow does around it — whose options they are, that they are
 * single use, and what the account looks like afterwards.
 */
beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $this->token = app(UserInviteService::class)->issue($this->user);
    $this->placeholder = $this->user->fresh()->password;
});

function expectPasskeyStored(User $user): void
{
    test()->mock(StorePasskeyAction::class)
        ->shouldReceive('execute')
        ->once()
        ->andReturnUsing(fn () => secondFactorPasskey($user));
}

it('hands out registration options for the invited account', function () {
    $response = $this->getJson("/api/auth/invite/{$this->token}/passkey-options")->assertOk();

    expect($response->json('user.name'))->toBe('ada@example.com');

    expect(session('invites.passkey-registration.user_id'))->toBe($this->user->id);
});

it('hands out no options for a dead invite', function () {
    $this->getJson('/api/auth/invite/not-a-token/passkey-options')->assertNotFound();
});

it('accepts the invite with a passkey and signs them in', function () {
    expectPasskeyStored($this->user);

    $response = $this->withSession([
        'invites.passkey-registration' => ['user_id' => $this->user->id, 'options' => '{}'],
    ])->postJson("/api/auth/invite/{$this->token}/passkey", ['id' => 'credential'])->assertOk();

    // The first passkey mints the account's recovery codes, and this is where they are shown.
    expect($response->json('recovery_codes'))->toHaveCount(8);

    $this->assertAuthenticatedAs($this->user);

    $user = $this->user->fresh();

    expect(UserInvite::query()->count())->toBe(0)
        ->and($user->passkeys()->count())->toBe(1)
        // No password was chosen, so the unguessable placeholder stays.
        ->and($user->password)->toBe($this->placeholder)
        ->and(Hash::check('', $user->password))->toBeFalse();

    $accepted = AuditLog::query()->where('event', AuditEvent::AUTH_INVITE_ACCEPTED)->sole();

    expect($accepted->properties['method'])->toBe('passkey')
        ->and(AuditLog::query()->where('event', AuditEvent::ACCOUNT_PASSKEY_CREATED)->count())->toBe(1);
});

it('spends the registration options', function () {
    expectPasskeyStored($this->user);

    $this->withSession([
        'invites.passkey-registration' => ['user_id' => $this->user->id, 'options' => '{}'],
    ])->postJson("/api/auth/invite/{$this->token}/passkey", ['id' => 'credential'])->assertOk();

    expect(session()->has('invites.passkey-registration'))->toBeFalse();
});

it('refuses options issued for a different invite', function () {
    // Two invites opened in one browser must not be able to spend each other's challenge.
    $other = User::factory()->create();

    $this->mock(StorePasskeyAction::class)->shouldNotReceive('execute');

    $this->withSession([
        'invites.passkey-registration' => ['user_id' => $other->id, 'options' => '{}'],
    ])->postJson("/api/auth/invite/{$this->token}/passkey", ['id' => 'credential'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');

    $this->assertGuest();
    expect(UserInvite::query()->count())->toBe(1);
});

it('refuses a passkey that was never issued options', function () {
    $this->mock(StorePasskeyAction::class)->shouldNotReceive('execute');

    $this->postJson("/api/auth/invite/{$this->token}/passkey", ['id' => 'credential'])
        ->assertBadRequest()
        ->assertJsonPath('code', 'invalid_passkey');
});

it('refuses a passkey for a dead invite', function () {
    app(UserInviteService::class)->revoke($this->user);

    $this->withSession([
        'invites.passkey-registration' => ['user_id' => $this->user->id, 'options' => '{}'],
    ])->postJson("/api/auth/invite/{$this->token}/passkey", ['id' => 'credential'])->assertNotFound();

    $this->assertGuest();
});

it('records which way a password invite was accepted', function () {
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);

    $this->postJson("/api/auth/invite/{$this->token}", [
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertNoContent();

    expect(AuditLog::query()->where('event', AuditEvent::AUTH_INVITE_ACCEPTED)->sole()->properties['method'])
        ->toBe('password');
});
