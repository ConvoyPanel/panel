<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;

it('hides admin-only events from the client-visible scope', function () {
    AuditLog::query()->create(['event' => AuditEvent::SERVER_POWER_SENT, 'properties' => []]);
    AuditLog::query()->create(['event' => AuditEvent::ADMIN_USER_SSO_TOKEN_GENERATED, 'properties' => []]);

    expect(AuditLog::query()->clientVisible()->pluck('event')->all())
        ->toBe([AuditEvent::SERVER_POWER_SENT]);
});

it('scopes by subject', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = new AuditLog(['event' => AuditEvent::ADMIN_USER_UPDATED, 'properties' => []]);
    $mine->subject()->associate($user);
    $mine->save();

    $theirs = new AuditLog(['event' => AuditEvent::ADMIN_USER_UPDATED, 'properties' => []]);
    $theirs->subject()->associate($other);
    $theirs->save();

    expect(AuditLog::query()->forSubject($user)->pluck('id')->all())->toBe([$mine->id]);
});

it('scopes by actor and event', function () {
    $user = User::factory()->create();

    $power = new AuditLog(['event' => AuditEvent::SERVER_POWER_SENT, 'properties' => []]);
    $power->actor()->associate($user);
    $power->save();

    $rename = new AuditLog(['event' => AuditEvent::SERVER_RENAMED, 'properties' => []]);
    $rename->actor()->associate($user);
    $rename->save();

    expect(AuditLog::query()->forActor($user)->count())->toBe(2)
        ->and(AuditLog::query()->forEvent(AuditEvent::SERVER_RENAMED)->pluck('id')->all())
        ->toBe([$rename->id]);
});

it('has no updated_at column to rewrite history with', function () {
    $log = AuditLog::query()->create(['event' => AuditEvent::SERVER_POWER_SENT, 'properties' => []]);

    expect(AuditLog::UPDATED_AT)->toBeNull()
        ->and($log->getAttributes())->not->toHaveKey('updated_at');
});
