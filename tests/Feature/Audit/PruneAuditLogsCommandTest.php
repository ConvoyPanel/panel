<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;

function auditEntry(AuditEvent $event, string $age): AuditLog
{
    return AuditLog::query()->create([
        'event' => $event,
        'properties' => [],
        'created_at' => now()->sub($age),
    ]);
}

it('deletes operational entries older than the window', function () {
    auditEntry(AuditEvent::SERVER_POWER_SENT, '100 days');

    $this->artisan('maintenance:prune-audit-logs', ['--prune-days' => 90])->assertSuccessful();

    expect(AuditLog::query()->count())->toBe(0);
});

it('keeps operational entries inside the window', function () {
    auditEntry(AuditEvent::SERVER_POWER_SENT, '10 days');

    $this->artisan('maintenance:prune-audit-logs', ['--prune-days' => 90])->assertSuccessful();

    expect(AuditLog::query()->count())->toBe(1);
});

it('never deletes security events however old they are', function () {
    auditEntry(AuditEvent::AUTH_LOGIN_FAILED, '10 years');
    auditEntry(AuditEvent::ACCOUNT_PASSWORD_UPDATED, '10 years');
    auditEntry(AuditEvent::ADMIN_TOKEN_CREATED, '10 years');
    auditEntry(AuditEvent::SERVER_POWER_SENT, '10 years');

    $this->artisan('maintenance:prune-audit-logs', ['--prune-days' => 90])->assertSuccessful();

    expect(AuditLog::query()->pluck('event')->all())
        ->toEqualCanonicalizing([
            AuditEvent::AUTH_LOGIN_FAILED,
            AuditEvent::ACCOUNT_PASSWORD_UPDATED,
            AuditEvent::ADMIN_TOKEN_CREATED,
        ]);
});

it('deletes across more rows than one chunk', function () {
    // Guards the do/while: an off-by-one in the loop condition would silently leave the tail behind
    // and nothing else in the suite would notice.
    config()->set('audit.prune_chunk', 2);

    for ($i = 0; $i < 7; $i++) {
        auditEntry(AuditEvent::SERVER_POWER_SENT, '100 days');
    }

    $this->artisan('maintenance:prune-audit-logs', ['--prune-days' => 90])->assertSuccessful();

    expect(AuditLog::query()->count())->toBe(0);
});

it('falls back to the configured window', function () {
    config()->set('audit.prune_days', 30);
    auditEntry(AuditEvent::SERVER_POWER_SENT, '45 days');

    $this->artisan('maintenance:prune-audit-logs')->assertSuccessful();

    expect(AuditLog::query()->count())->toBe(0);
});

it('refuses to run without a usable window', function () {
    config()->set('audit.prune_days', null);

    $this->artisan('maintenance:prune-audit-logs')->assertFailed();
})->throws(InvalidArgumentException::class);
