<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/*
 * Authentication is audited from Laravel's events rather than from call sites, because the
 * controllers behind it are Fortify's. These drive the events the way a real sign-in does.
 */

it('records a successful login against the user who logged in', function () {
    $user = User::factory()->create();

    Auth::login($user);

    $log = AuditLog::query()->forEvent(AuditEvent::AUTH_LOGIN_SUCCEEDED)->sole();

    expect($log->actor->is($user))->toBeTrue()
        ->and($log->subject->is($user))->toBeTrue()
        ->and($log->actor_label)->toBe($user->name);
});

it('records a logout', function () {
    $user = User::factory()->create();

    Auth::login($user);
    Auth::logout();

    expect(AuditLog::query()->forEvent(AuditEvent::AUTH_LOGOUT)->sole()->actor->is($user))->toBeTrue();
});

it('records a failed login with the attempted email but never the password', function () {
    User::factory()->create(['email' => 'target@example.com']);

    Auth::attempt(['email' => 'target@example.com', 'password' => 'wrong-password-value']);

    $log = AuditLog::query()->forEvent(AuditEvent::AUTH_LOGIN_FAILED)->sole();

    // No actor: nobody authenticated. The identifier is what makes a run of attempts against one
    // account legible in the log.
    expect($log->actor)->toBeNull()
        ->and($log->properties['email'])->toBe('target@example.com')
        ->and(json_encode($log->getAttributes()))->not->toContain('wrong-password-value');
});

it('keeps authentication events forever', function () {
    $user = User::factory()->create();
    Auth::login($user);

    $log = AuditLog::query()->forEvent(AuditEvent::AUTH_LOGIN_SUCCEEDED)->sole();

    $this->artisan('maintenance:prune-audit-logs', ['--prune-days' => 1])->assertSuccessful();

    expect(AuditLog::query()->whereKey($log->id)->exists())->toBeTrue();
});
