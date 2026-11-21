<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserInvite;
use App\Notifications\PasswordChanged;
use App\Notifications\PasswordResetRequested;
use App\Services\Auth\SessionRevocationService;
use App\Services\Users\UserInviteService;
use App\Settings\AccountSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    // PasswordPolicy checks HIBP's range API; an empty 200 is "not breached".
    Http::fake(['https://api.pwnedpasswords.com/*' => Http::response('', 200)]);
    Notification::fake();

    // Both preconditions on, so each test below is about the one thing it changes.
    $mail = app(MailSettings::class);
    $mail->host = 'smtp.example.com';
    $mail->save();

    setPasswordResets(true);

    $this->user = User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'old password that is long',
    ]);
});

function setPasswordResets(bool $on, bool $allowPasswordChange = true): void
{
    $settings = app(AccountSettings::class);
    $settings->allow_password_reset = $on;
    $settings->allow_password_change = $allowPasswordChange;
    $settings->save();
}

function resetBody(string $password = 'a brand new passphrase'): array
{
    return [
        'email' => 'ada@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ];
}

it('emails a reset link to the address that asked', function () {
    $this->postJson('/api/auth/password-reset', ['email' => 'ada@example.com'])
        ->assertNoContent();

    Notification::assertSentTo(
        $this->user,
        PasswordResetRequested::class,
        function (PasswordResetRequested $notification) {
            $link = $notification->toMail($this->user)->viewData['link'];

            return str_starts_with($link, config('app.url').'/auth/reset-password/')
                && str_ends_with($link, '?email=ada%40example.com');
        },
    );

    expect(AuditLog::query()->where('event', AuditEvent::AUTH_PASSWORD_RESET_REQUESTED)->sole()->subject_id)
        ->toBe($this->user->id);
});

it('answers an unknown address exactly like a known one', function () {
    $this->postJson('/api/auth/password-reset', ['email' => 'nobody@example.com'])
        ->assertNoContent();

    Notification::assertNothingSent();
});

it('is off until an admin turns it on', function () {
    setPasswordResets(false);

    $this->postJson('/api/auth/password-reset', ['email' => 'ada@example.com'])->assertNotFound();
    $this->postJson('/api/auth/password-reset/anything', resetBody())->assertNotFound();

    Notification::assertNothingSent();
});

it('is off when the panel has no mail relay', function () {
    $mail = app(MailSettings::class);
    $mail->host = '';
    $mail->save();
    config()->set('mail.default', 'array');

    $this->postJson('/api/auth/password-reset', ['email' => 'ada@example.com'])->assertNotFound();
});

it('tells the sign-in page whether to offer it', function () {
    $this->get('/auth/login')->assertSee('"passwordResets":true', false);

    setPasswordResets(false);

    $this->get('/auth/login')->assertSee('"passwordResets":false', false);
});

it('sends nothing to an account that may not change its password', function () {
    // Otherwise a reset would be the way around the operator's policy.
    setPasswordResets(true, allowPasswordChange: false);

    $this->postJson('/api/auth/password-reset', ['email' => 'ada@example.com'])
        ->assertNoContent();

    Notification::assertNothingSent();

    // The broker writes a token before the policy is consulted. It must not be left behind.
    expect(DB::table('password_reset_tokens')->count())->toBe(0);
});

it('still lets an admin reset when users may not change their password', function () {
    // Admins are exempt from the self-service policy everywhere else, so here too.
    setPasswordResets(true, allowPasswordChange: false);
    $admin = User::factory()->create(['email' => 'root@example.com', 'root_admin' => true]);

    $this->postJson('/api/auth/password-reset', ['email' => 'root@example.com'])->assertNoContent();

    Notification::assertSentTo($admin, PasswordResetRequested::class);
});

it('sends one link a minute per account', function () {
    $this->postJson('/api/auth/password-reset', ['email' => 'ada@example.com'])->assertNoContent();
    $this->postJson('/api/auth/password-reset', ['email' => 'ada@example.com'])->assertNoContent();

    Notification::assertSentToTimes($this->user, PasswordResetRequested::class, 1);
});

it('caps how many links one client can ask for', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/auth/password-reset', ['email' => "user{$i}@example.com"])->assertNoContent();
    }

    $this->postJson('/api/auth/password-reset', ['email' => 'user6@example.com'])->assertTooManyRequests();
});

it('says whether a link still works', function () {
    $token = Password::broker()->createToken($this->user);

    $this->getJson("/api/auth/password-reset/{$token}?email=ada@example.com")->assertNoContent();
    $this->getJson('/api/auth/password-reset/wrong?email=ada@example.com')->assertNotFound();
    $this->getJson("/api/auth/password-reset/{$token}?email=someone@example.com")->assertNotFound();
});

it('sets the new password and signs every session out', function () {
    $token = Password::broker()->createToken($this->user);
    $rememberToken = $this->user->remember_token;

    $this->mock(SessionRevocationService::class)
        ->shouldReceive('revokeAllForUser')
        ->once()
        ->withArgs(fn (User $user) => $user->is($this->user));

    $this->postJson("/api/auth/password-reset/{$token}", resetBody())->assertNoContent();

    $user = $this->user->fresh();

    expect(Hash::check('a brand new passphrase', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($rememberToken)
        ->and(DB::table('password_reset_tokens')->count())->toBe(0);

    // Proving the mailbox is not proving the second factor, so it does not sign anyone in.
    $this->assertGuest();

    Notification::assertSentTo($user, PasswordChanged::class);

    expect(AuditLog::query()->where('event', AuditEvent::AUTH_PASSWORD_RESET_COMPLETED)->count())->toBe(1);
});

it('works only once', function () {
    $token = Password::broker()->createToken($this->user);

    $this->postJson("/api/auth/password-reset/{$token}", resetBody())->assertNoContent();
    $this->postJson("/api/auth/password-reset/{$token}", resetBody('another new passphrase'))
        ->assertNotFound();

    expect(Hash::check('a brand new passphrase', $this->user->fresh()->password))->toBeTrue();
});

it('refuses a wrong token', function () {
    Password::broker()->createToken($this->user);

    $this->postJson('/api/auth/password-reset/not-the-token', resetBody())->assertNotFound();

    expect(Hash::check('old password that is long', $this->user->fresh()->password))->toBeTrue();
});

it('checks the policy again when the link is used', function () {
    // The email may sit in an inbox while the operator narrows the policy.
    $token = Password::broker()->createToken($this->user);

    setPasswordResets(true, allowPasswordChange: false);

    $this->postJson("/api/auth/password-reset/{$token}", resetBody())->assertNotFound();

    expect(Hash::check('old password that is long', $this->user->fresh()->password))->toBeTrue();
});

it('holds the new password to the usual policy', function () {
    $token = Password::broker()->createToken($this->user);

    $this->postJson("/api/auth/password-reset/{$token}", resetBody('short'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('password');
});

it('stops an outstanding invite from setting the password again', function () {
    app(UserInviteService::class)->issue($this->user);
    $token = Password::broker()->createToken($this->user);

    $this->postJson("/api/auth/password-reset/{$token}", resetBody())->assertNoContent();

    expect(UserInvite::query()->count())->toBe(0);
});
