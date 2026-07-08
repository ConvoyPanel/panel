<?php

use App\Data\User\SessionRecordData;
use App\Models\SessionRecord;
use App\Models\User;

function seedSession(User $user, string $sessionId, array $attrs = []): SessionRecord
{
    return SessionRecord::query()->create(array_merge([
        'session_id' => $sessionId,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'Mozilla/5.0',
        'last_active_at' => now(),
    ], $attrs));
}

/** Make a session id "live" in the store so read-time reconciliation keeps its row. */
function storeSession(string $sessionId): void
{
    app('session')->getHandler()->write($sessionId, 'live');
}

it('records the current web session on an authenticated request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/client/user')->assertOk();

    expect(SessionRecord::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('does not record a bearer-token (sessionless) request', function () {
    $user = User::factory()->create();
    $plain = app(\App\Services\Api\CreateAccountTokenService::class)
        ->handle($user, 'cli', ['servers:read'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$plain}")
        ->getJson('/api/client/servers')
        ->assertOk();

    expect(SessionRecord::query()->count())->toBe(0);
});

it('lists only the current user\'s sessions', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    seedSession($user, 'sess-mine');
    seedSession($other, 'sess-theirs');
    storeSession('sess-mine'); // keep it live so reconciliation doesn't prune it

    // The current session is recorded *after* the response (middleware runs post-controller), so
    // this first request sees only the seeded row — which must be the user's own, not the other's.
    $this->actingAs($user)
        ->getJson('/api/client/account/sessions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', SessionRecord::where('session_id', 'sess-mine')->value('id'));

    expect(SessionRecord::query()->where('user_id', $user->id)->pluck('session_id'))
        ->toContain('sess-mine')
        ->not->toContain('sess-theirs');
});

it('prunes rows whose session no longer exists in the store', function () {
    $user = User::factory()->create();
    $ghost = seedSession($user, 'evicted-session'); // never written to the store

    $this->actingAs($user)
        ->getJson('/api/client/account/sessions')
        ->assertOk()
        ->assertJsonMissing(['id' => $ghost->id]);

    // The reconciliation also deletes the dead row.
    expect(SessionRecord::query()->whereKey($ghost->id)->exists())->toBeFalse();
});

it('revokes another session', function () {
    $user = User::factory()->create();
    $record = seedSession($user, 'other-device');

    $this->actingAs($user)
        ->deleteJson("/api/client/account/sessions/{$record->id}")
        ->assertNoContent();

    expect(SessionRecord::query()->whereKey($record->id)->exists())->toBeFalse();
});

it('only lets a user revoke their own session', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $record = seedSession($other, 'victim-device');

    $this->actingAs($user)
        ->deleteJson("/api/client/account/sessions/{$record->id}")
        ->assertNotFound();

    expect(SessionRecord::query()->whereKey($record->id)->exists())->toBeTrue();
});

it('marks the requesting session as current', function () {
    $user = User::factory()->create();
    $record = seedSession($user, 'abc123');

    expect(SessionRecordData::fromModel($record, 'abc123')->isCurrent)->toBeTrue()
        ->and(SessionRecordData::fromModel($record, 'different')->isCurrent)->toBeFalse();
});
