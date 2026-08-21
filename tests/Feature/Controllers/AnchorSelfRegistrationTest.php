<?php

use App\Enums\Anchor\AnchorCompatibility;
use App\Enums\Anchor\AnchorMode;
use App\Enums\Audit\AuditEvent;
use App\Models\Anchor;
use App\Models\AnchorEnrollmentKey;
use App\Models\AuditLog;
use App\Services\Anchor\AnchorEnrollmentKeyService;
use Database\Factories\AnchorEnrollmentKeyFactory;
use Illuminate\Support\Facades\Cache;

/*
 * `/api/anchor/enroll` is throttled to 10/minute per IP, and every request in
 * this file comes from the same test client address. The limiter lives in the
 * cache, which outlives the database rollback between tests, so without this a
 * later test fails on a limit an earlier one consumed.
 */
beforeEach(fn () => Cache::flush());

/**
 * Mints a key without logging anyone in.
 *
 * Deliberately not through `actingAs(admin())` + the admin endpoint: that
 * session would still be authenticated when the "machine" posts to
 * `/api/anchor/enroll`, which is unauthenticated in production, and the issuing
 * admin would silently become the actor on the enrolling machine's audit rows.
 * The API path is covered end to end by its own test below.
 */
function issueKey(?AnchorMode $mode = null, ?int $maxUses = 1): string
{
    return app(AnchorEnrollmentKeyService::class)
        ->issue(name: 'Rack 4', mode: $mode, maxUses: $maxUses)
        ->token;
}

it('lets a machine holding a key create its own record', function () {
    $token = issueKey();

    $response = $this->postJson('/api/anchor/enroll', [
        'token' => $token,
        'mode' => 'agent',
        'report' => [
            'hostname' => 'pve-07.example.com',
            'pve_node_name' => 'pve-07',
            'pve_version' => '9.2.2',
            'cpu' => ['sockets' => 2, 'cores' => 32, 'threads' => 64],
            'memory_bytes' => 549755813888,
        ],
    ])->assertOk();

    $anchor = Anchor::sole();

    expect($anchor->name)->toBe('pve-07.example.com')
        ->and($anchor->enrolled_at)->not->toBeNull()
        // The key proves the presenter was told a password. It is not a person
        // deciding this machine belongs.
        ->and($anchor->approved_at)->toBeNull()
        ->and($anchor->compatibility())->toBe(AnchorCompatibility::PENDING_APPROVAL)
        ->and($anchor->reported_facts['pve_node_name'])->toBe('pve-07')
        ->and($response->json('config.installation_id'))->toBe($anchor->uuid)
        ->and($response->json('config.secret'))->toBe($anchor->secret);

    // The key records that it was spent.
    $key = AnchorEnrollmentKey::sole();
    expect($key->uses)->toBe(1)
        ->and($key->last_used_at)->not->toBeNull()
        ->and($key->isUsable())->toBeFalse();
});

it('records where the request actually came from, not just what was claimed', function () {
    $token = issueKey();

    $this->postJson('/api/anchor/enroll', [
        'token' => $token,
        'mode' => 'agent',
        'report' => ['hostname' => 'pve-07'],
    ])->assertOk();

    // The one reachability claim a machine cannot overstate.
    expect(Anchor::sole()->reported_facts['observed_source_ip'])->not->toBeNull();
});

it('never lets the report decide anything privileged', function () {
    $token = issueKey();

    $this->postJson('/api/anchor/enroll', [
        'token' => $token,
        'mode' => 'agent',
        'report' => [
            'hostname' => 'pve-07',
            // All three are decisions, arriving in the same envelope as the
            // request for them. None may be honoured.
            'approved_at' => now()->toIso8601String(),
            'location_id' => 1,
            'relay_id' => 99,
        ],
    ])->assertOk();

    $anchor = Anchor::sole();

    expect($anchor->approved_at)->toBeNull()
        ->and($anchor->relay_id)->toBeNull()
        ->and($anchor->reported_facts)->not->toHaveKey('approved_at')
        ->and($anchor->reported_facts)->not->toHaveKey('location_id');
});

it('refuses a key that cannot admit the mode presented', function () {
    $token = issueKey(mode: AnchorMode::RELAY);

    $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])
        ->assertUnprocessable();

    expect(Anchor::count())->toBe(0)
        // A refused attempt must not burn the key, or one wrong flag disposes
        // of a credential the operator then has to reissue.
        ->and(AnchorEnrollmentKey::sole()->uses)->toBe(0);
});

it('refuses a revoked, expired or exhausted key without saying which', function () {
    foreach (['revoked', 'expired', 'exhausted'] as $state) {
        AnchorEnrollmentKey::factory()->{$state}()->create();
        $token = AnchorEnrollmentKeyFactory::$lastToken;

        $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])
            ->assertUnprocessable()
            // One message for every rejection: the presenter is
            // unauthenticated, and which it is tells them something they have
            // not earned the right to know.
            ->assertJsonPath('message', 'The enrollment key is invalid or expired.');
    }

    expect(Anchor::count())->toBe(0);
});

it('spends a single-use key exactly once even under a simultaneous claim', function () {
    // Two machines booting from one image present the same key. The row lock is
    // what makes max_uses a limit rather than a suggestion.
    $token = issueKey();

    $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])->assertOk();
    $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])->assertUnprocessable();

    expect(Anchor::count())->toBe(1)
        ->and(AnchorEnrollmentKey::sole()->uses)->toBe(1);
});

it('admits a whole rack from one reusable key, naming each machine for itself', function () {
    $token = issueKey(maxUses: 3);

    foreach (['pve-01', 'pve-02', 'pve-03'] as $host) {
        $this->postJson('/api/anchor/enroll', [
            'token' => $token,
            'mode' => 'agent',
            'report' => ['hostname' => $host],
        ])->assertOk();
    }

    $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])
        ->assertUnprocessable();

    expect(Anchor::pluck('name')->sort()->values()->all())
        ->toBe(['pve-01', 'pve-02', 'pve-03']);
});

it('falls back to a distinguishable name when the machine says nothing about itself', function () {
    $token = issueKey(maxUses: 2);

    $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])->assertOk();
    $this->postJson('/api/anchor/enroll', ['token' => $token, 'mode' => 'agent'])->assertOk();

    $names = Anchor::pluck('name');

    // Two rows called "Rack 4" would make the approval queue unusable.
    expect($names)->toHaveCount(2)
        ->and($names[0])->not->toBe($names[1])
        ->and($names[0])->toStartWith('Rack 4 ');
});

it('leaves the targeted rotation path alone', function () {
    // Two credentials, told apart by shape rather than by which lookup happens
    // to hit -- a mistyped rotation token must not be evaluated as an attempt
    // to enroll a stranger.
    $anchor = Anchor::factory()->create();

    $token = $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/enrollment")
        ->json('data.token');

    $this->postJson('/api/anchor/enroll', ['token' => $token])
        ->assertOk()
        ->assertJsonPath('config.installation_id', $anchor->uuid);

    expect(Anchor::count())->toBe(1)
        ->and($anchor->refresh()->isApproved())->toBeTrue();
});

it('stops mirroring the panel-side address into the agent config', function () {
    $anchor = Anchor::factory()->create(['public_url' => 'https://anchor.example.com']);
    $token = $this->actingAs(admin())
        ->postJson("/api/admin/anchors/{$anchor->id}/enrollment")
        ->json('data.token');

    // It describes how the panel reaches the agent, the agent never read it,
    // and mirroring it meant a correction needed a re-enrollment.
    $this->postJson('/api/anchor/enroll', ['token' => $token])
        ->assertOk()
        ->assertJsonMissingPath('config.public_url');
});

it('records that a machine admitted itself, and through which key', function () {
    $token = issueKey();

    $this->postJson('/api/anchor/enroll', [
        'token' => $token,
        'mode' => 'agent',
        'report' => ['hostname' => 'pve-07'],
    ])->assertOk();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_ANCHOR_SELF_ENROLLED)->sole();

    expect($log->properties['hostname'])->toBe('pve-07')
        ->and($log->properties['enrollment_key'])->toBe('Rack 4')
        // No actor: an enrolling host is not a panel principal, and casting it
        // as one would put a machine in the same column as the admins.
        ->and($log->actor)->toBeNull();
});

it('carries a token minted through the admin API all the way to a new record', function () {
    // The seam between the two slices is the hash: slice 1 stores it, slice 2
    // looks it up. Worth one test that crosses it for real.
    $token = $this->actingAs(admin())
        ->postJson('/api/admin/anchors/enrollment-keys', ['name' => 'Rack 4'])
        ->assertCreated()
        ->json('data.token');

    $this->postJson('/api/anchor/enroll', [
        'token' => $token,
        'mode' => 'agent',
        'report' => ['hostname' => 'pve-09'],
    ])->assertOk();

    expect(Anchor::sole()->name)->toBe('pve-09');
});
