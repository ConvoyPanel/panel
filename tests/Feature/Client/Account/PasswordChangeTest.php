<?php

use App\Models\SessionRecord;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

/** A passphrase: no uppercase, digits or symbols, and stronger than anything that has them. */
const NEW_PASSPHRASE = 'exuberant plywood cascade lantern';

function seedOtherSession(User $user, string $sessionId = 'other-device'): SessionRecord
{
    return SessionRecord::query()->create([
        'session_id' => $sessionId,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'Mozilla/5.0',
        'last_active_at' => now(),
    ]);
}

function changePassword(User $user, array $overrides = []): TestResponse
{
    // Default the breach check to "not found", i.e. the password is not breached. Registered here,
    // at call time, so a fake a test set up beforehand is matched first and wins (Http::fake is
    // first-match-wins). Without this the suite-wide Http::preventStrayRequests() would throw,
    // `uncompromised()` would swallow it and fail open, and every test below would pass because the
    // check *errored* rather than because it succeeded.
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);

    return test()->actingAs($user)->putJson('/api/client/account/password', array_merge([
        'current_password' => 'Password123!',
        'password' => NEW_PASSPHRASE,
        'password_confirmation' => NEW_PASSPHRASE,
    ], $overrides));
}

beforeEach(function () {
    Notification::fake();

    $this->user = User::factory()->create(['password' => Hash::make('Password123!')]);
});

it('revokes every other session but keeps the current one', function () {
    $other = seedOtherSession($this->user);

    changePassword($this->user)->assertNoContent();

    // The other device is gone from the store *and* the metadata table, so a stolen cookie stops
    // working rather than merely stopping being listed.
    expect(SessionRecord::query()->whereKey($other->id)->exists())->toBeFalse();

    // Changing your password must not log you out of the tab you changed it in.
    expect($this->app['auth']->guard('web')->check())->toBeTrue();
});

it('cycles the remember token', function () {
    $this->user->forceFill(['remember_token' => 'stale-remember-token'])->save();

    changePassword($this->user)->assertNoContent();

    // A remember-me cookie authenticates on its own, so an un-cycled token would walk an evicted
    // device straight back in and make the revocation above cosmetic.
    expect($this->user->fresh()->remember_token)->not->toBe('stale-remember-token');
});

it('notifies the account owner out of band', function () {
    changePassword($this->user)->assertNoContent();

    Notification::assertSentTo($this->user, PasswordChanged::class);
});

it('accepts a passphrase with no uppercase, digits or symbols', function () {
    changePassword($this->user)->assertNoContent();

    expect(Hash::check(NEW_PASSPHRASE, $this->user->fresh()->password))->toBeTrue();
});

it('rejects a password that appears in a breach corpus', function () {
    $breached = 'trombone silhouette marmalade';
    $hash = strtoupper(sha1($breached));

    // HIBP's k-anonymity range API: only the leading 5 hash characters are sent, and the reply
    // lists the matching suffixes with their breach counts.
    Http::fake([
        'https://api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(
            substr($hash, 5).":42\r\n",
        ),
    ]);

    changePassword($this->user, ['password' => $breached, 'password_confirmation' => $breached])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('lets a password through when the breach service is unreachable', function () {
    $password = 'trombone silhouette marmalade';

    Http::fake([
        'https://api.pwnedpasswords.com/*' => Http::response('', 500),
    ]);

    // Deliberate: the rule fails open, so an air-gapped or rate-limited install can still change
    // passwords rather than locking everyone out of their own account. Pinned here because it is
    // a silent security-relevant default — if it ever fails *closed*, that should be a decision.
    changePassword($this->user, ['password' => $password, 'password_confirmation' => $password])
        ->assertNoContent();
});

it('rejects a passphrase bcrypt would silently truncate', function () {
    // 73 bytes: one past what bcrypt hashes. Accepting it would mean only the leading 72 bytes
    // ever authenticate.
    $tooLong = str_repeat('a', 73);

    changePassword($this->user, ['password' => $tooLong, 'password_confirmation' => $tooLong])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('measures the length ceiling in bytes, not characters', function () {
    // 40 multibyte characters = 120 bytes. `max:72` counts characters (mb_strlen) and would wave
    // this through at nearly double bcrypt's limit.
    $multibyte = str_repeat('é', 40);

    changePassword($this->user, ['password' => $multibyte, 'password_confirmation' => $multibyte])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('still requires the current password, and a failed attempt has no side effects', function () {
    $other = seedOtherSession($this->user);

    changePassword($this->user, ['current_password' => 'not-the-password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('current_password');

    expect(SessionRecord::query()->whereKey($other->id)->exists())->toBeTrue();
    Notification::assertNothingSent();
});
