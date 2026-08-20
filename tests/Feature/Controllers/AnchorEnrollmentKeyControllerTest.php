<?php

use App\Enums\Anchor\AnchorMode;
use App\Enums\Anchor\EnrollmentKeyStatus;
use App\Enums\Audit\AuditEvent;
use App\Models\AnchorEnrollmentKey;
use App\Models\AuditLog;
use App\Services\Anchor\AnchorEnrollmentKeyService;

it('shows the token once, at creation, and never again', function () {
    $response = $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'Rack 4'])
        ->assertCreated();

    $token = $response->json('data.token');

    expect($token)->toStartWith(AnchorEnrollmentKeyService::TOKEN_PREFIX)
        ->and($response->json('data.command'))->toContain($token)
        // Only the digest is kept, so the roster physically cannot show it again.
        ->and(AnchorEnrollmentKey::sole()->token_hash)->toBe(hash('sha256', $token));

    $index = $this->actingAs(admin())
        ->getJson('/api/admin/anchors/enrollment-keys')
        ->assertOk();

    expect($index->json('items.0.token'))->toBeNull()
        ->and($index->json('items.0.command'))->toBeNull()
        ->and($index->json('items.0'))->not->toHaveKey('tokenHash');
});

it('defaults to a single-use, short-lived key', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'One box'])
        ->assertCreated()
        ->assertJsonPath('data.maxUses', 1)
        ->assertJsonPath('data.mode', null);

    $key = AnchorEnrollmentKey::sole();

    expect($key->expires_at)->not->toBeNull()
        ->and($key->expires_at->diffInMinutes(now()))
        ->toEqualWithDelta(-AnchorEnrollmentKeyService::DEFAULT_TTL_MINUTES, 1);
});

it('makes the dangerous shape reachable only by asking for it explicitly', function () {
    // An unlimited, never-expiring key is expressible -- a machine image needs
    // one -- but you cannot arrive at it by omitting a parameter.
    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', [
            'name' => 'Golden image',
            'max_uses' => null,
            'expires_in_minutes' => null,
        ])
        ->assertCreated()
        ->assertJsonPath('data.maxUses', null)
        ->assertJsonPath('data.expiresAt', null);

    expect(AnchorEnrollmentKey::sole()->status())->toBe(EnrollmentKeyStatus::ACTIVE);
});

it('scopes a key to one mode when asked', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'Relays only', 'mode' => 'relay'])
        ->assertCreated()
        ->assertJsonPath('data.mode', 'relay');

    $key = AnchorEnrollmentKey::sole();

    expect($key->permits(AnchorMode::RELAY))->toBeTrue()
        ->and($key->permits(AnchorMode::AGENT))->toBeFalse();

    // A key with no mode admits either, which is why `permits()` exists rather
    // than an equality check at the call site.
    $open = AnchorEnrollmentKey::factory()->create(['mode' => null]);

    expect($open->permits(AnchorMode::AGENT))->toBeTrue()
        ->and($open->permits(AnchorMode::RELAY))->toBeTrue();
});

it('derives every terminal status without anyone writing to the row', function () {
    expect(AnchorEnrollmentKey::factory()->create()->status())->toBe(EnrollmentKeyStatus::ACTIVE)
        ->and(AnchorEnrollmentKey::factory()->revoked()->create()->status())->toBe(EnrollmentKeyStatus::REVOKED)
        ->and(AnchorEnrollmentKey::factory()->expired()->create()->status())->toBe(EnrollmentKeyStatus::EXPIRED)
        ->and(AnchorEnrollmentKey::factory()->exhausted()->create()->status())->toBe(EnrollmentKeyStatus::EXHAUSTED);
});

it('keeps the usable scope and status() in agreement', function () {
    // They are two implementations of one question -- SQL and PHP -- so the
    // only thing worth asserting is that they never disagree.
    AnchorEnrollmentKey::factory()->create();
    AnchorEnrollmentKey::factory()->unlimited()->create();
    AnchorEnrollmentKey::factory()->revoked()->create();
    AnchorEnrollmentKey::factory()->expired()->create();
    AnchorEnrollmentKey::factory()->exhausted()->create();

    $usableIds = AnchorEnrollmentKey::query()->usable()->pluck('id')->sort()->values();
    $activeIds = AnchorEnrollmentKey::all()
        ->filter->isUsable()
        ->pluck('id')->sort()->values();

    expect($usableIds->all())->toBe($activeIds->all())
        ->and($usableIds)->toHaveCount(2);
});

it('withdraws a key without erasing what it admitted', function () {
    $key = AnchorEnrollmentKey::factory()->create(['uses' => 3, 'max_uses' => null]);

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/enrollment-keys/{$key->id}/revoke")
        // 201 rather than 200 because laravel-data answers any POST that way;
        // the same is already true of POST /anchors/{anchor}/enrollment.
        ->assertCreated()
        ->assertJsonPath('data.status', 'revoked');

    $key->refresh();

    expect($key->exists)->toBeTrue()
        ->and($key->uses)->toBe(3)
        ->and($key->revoked_at)->not->toBeNull();
});

it('refuses to delete a key that can still admit a machine', function () {
    $key = AnchorEnrollmentKey::factory()->create();

    // Deleting an active key would be a quieter revocation, and the quiet path
    // is the one taken when someone would rather leave no roster entry.
    $this->actingAs(admin())
        ->deleteJson("/api/admin/anchors/enrollment-keys/{$key->id}")
        ->assertStatus(400);

    expect(AnchorEnrollmentKey::find($key->id))->not->toBeNull();

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/enrollment-keys/{$key->id}/revoke")
        ->assertCreated();

    $this->actingAs(admin())
        ->deleteJson("/api/admin/anchors/enrollment-keys/{$key->id}")
        ->assertNoContent();

    expect(AnchorEnrollmentKey::find($key->id))->toBeNull();
});

it('records the terms of a key but never its token', function () {
    $response = $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', [
            'name' => 'Rack 4',
            'mode' => 'agent',
            'max_uses' => 5,
        ])
        ->assertCreated();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_ANCHOR_ENROLLMENT_KEY_CREATED)->sole();

    expect($log->properties['name'])->toBe('Rack 4')
        ->and($log->properties['mode'])->toBe('agent')
        ->and($log->properties['max_uses'])->toBe(5)
        ->and(json_encode($log->properties))->not->toContain($response->json('data.token'));
});

it('rejects a key request that only an admin may make', function () {
    $this->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'No auth'])
        ->assertUnauthorized();

    expect(AnchorEnrollmentKey::count())->toBe(0);
});

it('validates the terms it is given', function () {
    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => ''])
        ->assertJsonValidationErrors('name');

    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'x', 'mode' => 'router'])
        ->assertJsonValidationErrors('mode');

    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'x', 'max_uses' => 0])
        ->assertJsonValidationErrors('max_uses');

    $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'x', 'expires_in_minutes' => 525601])
        ->assertJsonValidationErrors('expires_in_minutes');
});
