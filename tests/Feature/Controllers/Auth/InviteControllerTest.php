<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserInvite;
use App\Notifications\UserInvited;
use App\Services\Users\UserInviteService;
use App\Settings\MailSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);

    // PasswordPolicy checks HIBP's range API. An empty 200 is "this prefix matched nothing",
    // i.e. not breached — faked rather than left to fail open, so a policy test that passes is
    // passing because the password is fine and not because the lookup errored.
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);
});

it('creates an account without a password and hands back a link', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)
        ->postJson('/api/admin/users', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'root_admin' => false,
        ])
        ->assertOk();

    $link = $response->json('invite.link');

    expect($link)->toStartWith(config('app.url').'/auth/invite/')
        ->and($response->json('invite.emailed'))->toBeTrue();

    // The token lives in the URL and only its hash is stored, so a database read cannot
    // reconstruct a working link.
    $token = str($link)->afterLast('/')->toString();
    $invite = UserInvite::query()->sole();

    expect($invite->token)->toBe(hash('sha256', $token))
        ->and($invite->token)->not->toBe($token);

    Notification::assertSentTo(User::where('email', 'ada@example.com')->sole(), UserInvited::class);
});

it('leaves an invited account unusable until the link is redeemed', function () {
    Notification::fake();

    $this->actingAs($this->admin)->postJson('/api/admin/users', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'root_admin' => false,
    ]);

    // The placeholder is 64 random characters nobody has seen — not a blank, and not guessable.
    $user = User::where('email', 'ada@example.com')->sole();

    expect(Hash::check('', $user->password))->toBeFalse()
        ->and(Hash::check('password', $user->password))->toBeFalse();
});

it('still creates with a password when one is given', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->postJson('/api/admin/users', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'password' => 'correct horse battery staple',
            'root_admin' => false,
        ])
        ->assertSuccessful()
        ->assertJsonMissingPath('invite');

    expect(UserInvite::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('returns the link but does not email it when mail is off', function () {
    Notification::fake();

    // The state a fresh self-hosted install is in. User creation must not depend on a relay.
    $settings = app(MailSettings::class);
    $settings->host = '';
    $settings->save();
    config()->set('mail.default', 'array');

    $this->actingAs($this->admin)
        ->postJson('/api/admin/users', [
            'name' => 'Ada',
            'email' => 'ada@example.com',
            'root_admin' => false,
        ])
        ->assertOk()
        ->assertJsonPath('invite.emailed', false);

    expect(UserInvite::query()->count())->toBe(1);
    Notification::assertNothingSent();
});

it('describes the invite to the person holding the link', function () {
    $user = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
    $token = app(UserInviteService::class)->issue($user);

    $this->getJson('/api/auth/invite/'.$token)
        ->assertOk()
        ->assertJsonPath('data.name', 'Ada')
        ->assertJsonPath('data.email', 'ada@example.com');
});

it('answers the same way for an unknown, spent or expired token', function () {
    $user = User::factory()->create();
    $token = app(UserInviteService::class)->issue($user);

    UserInvite::query()->sole()->update([
        'expires_at' => CarbonImmutable::now()->subMinute(),
    ]);

    // Distinguishing these would confirm that a guessed token once meant something.
    $this->getJson('/api/auth/invite/'.$token)->assertNotFound();
    $this->getJson('/api/auth/invite/'.str_repeat('a', 48))->assertNotFound();
});

it('sets the password, burns the invite and signs them in', function () {
    $user = User::factory()->create();
    $token = app(UserInviteService::class)->issue($user);

    $this->postJson('/api/auth/invite/'.$token, [
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertNoContent();

    expect(Hash::check('correct horse battery staple', $user->fresh()->password))->toBeTrue()
        ->and(UserInvite::query()->count())->toBe(0)
        ->and(auth()->id())->toBe($user->id);
});

it('refuses to redeem the same link twice', function () {
    $user = User::factory()->create();
    $token = app(UserInviteService::class)->issue($user);

    $this->postJson('/api/auth/invite/'.$token, [
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ])->assertNoContent();

    $this->postJson('/api/auth/invite/'.$token, [
        'password' => 'a different password entirely',
        'password_confirmation' => 'a different password entirely',
    ])->assertNotFound();
});

it('holds an invited password to the same policy as any other', function () {
    $user = User::factory()->create();
    $token = app(UserInviteService::class)->issue($user);

    $this->postJson('/api/auth/invite/'.$token, [
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertStatus(422)->assertJsonValidationErrors(['password']);
});

it('invalidates the previous link when a new one is issued', function () {
    $user = User::factory()->create();
    $invites = app(UserInviteService::class);

    $first = $invites->issue($user);
    $second = $invites->issue($user);

    // "Resend" has to mean the old link stops working, or a forwarded one can never be recalled.
    expect(UserInvite::query()->count())->toBe(1);
    $this->getJson('/api/auth/invite/'.$first)->assertNotFound();
    $this->getJson('/api/auth/invite/'.$second)->assertOk();
});

it('lets an admin revoke an outstanding invite', function () {
    $user = User::factory()->create();
    $token = app(UserInviteService::class)->issue($user);

    $this->actingAs($this->admin)
        ->deleteJson('/api/admin/users/'.$user->id.'/invite')
        ->assertNoContent();

    $this->getJson('/api/auth/invite/'.$token)->assertNotFound();

    expect(AuditLog::query()->where('event', AuditEvent::ADMIN_USER_INVITE_REVOKED)->exists())
        ->toBeTrue();
});

it('records the acceptance against the account itself', function () {
    $user = User::factory()->create();
    $token = app(UserInviteService::class)->issue($user);

    $this->postJson('/api/auth/invite/'.$token, [
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ]);

    $entry = AuditLog::query()->where('event', '=', AuditEvent::AUTH_INVITE_ACCEPTED)->sole();

    expect($entry->subject_id)->toBe($user->id);
});

it('is closed to a non-admin', function () {
    $user = User::factory()->create();

    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->postJson('/api/admin/users/'.$user->id.'/invite')
        ->assertForbidden();
});
