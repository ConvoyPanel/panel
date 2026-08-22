<?php

use App\Enums\Audit\AuditEvent;
use App\Models\AnchorEnrollment;
use App\Models\AuditLog;
use App\Models\Location;
use App\Models\Node;
use App\Models\Relay;
use App\Models\User;

/** The decisions a host cannot make for itself. */
function approvalPayload(array $overrides = []): array
{
    return [
        'location_id' => Location::factory()->create()->id,
        'memory_overallocate' => 0,
        'fqdn' => 'pve-new.example.com',
        'port' => 8006,
        'agent_public_url' => 'https://pve-new.example.com:2115',
        'token_id' => 'root@pam!convoy',
        'token_secret' => 'a-secret',
        'display_name' => 'pve-new',
        'name' => 'pve-new',
        'socket_count' => 2,
        'core_count' => 32,
        'cpu_count' => 64,
        'memory' => 549755813888,
        ...$overrides,
    ];
}

it('turns an approved enrollment into a node carrying the same identity', function () {
    $enrollment = AnchorEnrollment::factory()->create();
    $uuid = $enrollment->uuid;
    $secret = $enrollment->secret;

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/enrollments/{$enrollment->id}/approve", approvalPayload())
        ->assertCreated()
        ->assertJsonPath('data.displayName', 'pve-new');

    $node = Node::sole();

    // The agent wrote these to disk at enrollment and has been heartbeating
    // with them since. Minting new ones would drop the machine off moments
    // after letting it in.
    expect($node->agent_uuid)->toBe($uuid)
        ->and($node->agent_secret)->toBe($secret)
        ->and($node->agent_public_url)->toBe('https://pve-new.example.com:2115')
        ->and($node->hasAnchor())->toBeTrue()
        // One machine, one row.
        ->and(AnchorEnrollment::count())->toBe(0);
});

it('carries over what the machine reported without asking again', function () {
    $enrollment = AnchorEnrollment::factory()->create();

    $suggestions = $this->actingAs(admin())
        ->getJson("/api/admin/anchors/enrollments/{$enrollment->id}")
        ->assertOk()
        ->json('data.suggestions');

    // The approval screen is a confirmation of what was found, not a blank form.
    expect($suggestions['name'])->toBe('pve-new')
        ->and($suggestions['fqdn'])->toBe('pve-new.example.com')
        ->and($suggestions['socketCount'] ?? $suggestions['socket_count'])->toBe(2)
        ->and($suggestions['coreCount'] ?? $suggestions['core_count'])->toBe(32)
        ->and($suggestions['cpuCount'] ?? $suggestions['cpu_count'])->toBe(64)
        ->and($suggestions['memory'])->toBe(549755813888);

    // Capacity is reported; how far to oversubscribe it is not. That is policy,
    // and the host has no view on it.
    expect($suggestions)->not->toHaveKey('memoryOverallocate')
        ->and($suggestions)->not->toHaveKey('locationId');
});

it('will not let a machine choose its own location', function () {
    $enrollment = AnchorEnrollment::factory()->create();

    $this->actingAs(admin())
        ->postJson(
            "/api/admin/anchors/enrollments/{$enrollment->id}/approve",
            collect(approvalPayload())->except('location_id')->all(),
        )
        ->assertJsonValidationErrors('location_id');

    expect(Node::count())->toBe(0)
        ->and(AnchorEnrollment::count())->toBe(1);
});

it('requires the address the machine could not know', function () {
    $enrollment = AnchorEnrollment::factory()->create();

    // It knows which interface it bound to. It cannot know how this panel
    // routes back through whatever NAT or split-horizon DNS sits between.
    $this->actingAs(admin())
        ->postJson(
            "/api/admin/anchors/enrollments/{$enrollment->id}/approve",
            collect(approvalPayload())->except('agent_public_url')->all(),
        )
        ->assertJsonValidationErrors('agent_public_url');
});

it('promotes a relay enrollment into a relay, not a node', function () {
    $enrollment = AnchorEnrollment::factory()->relay()->create();

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/enrollments/{$enrollment->id}/approve", [
            'name' => 'London relay',
            'public_url' => 'https://relay.example.com',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'London relay');

    expect(Relay::sole()->uuid)->toBe($enrollment->uuid)
        ->and(Node::count())->toBe(0)
        ->and(AnchorEnrollment::count())->toBe(0);
});

it('turns a machine away without leaving it able to talk to us', function () {
    $enrollment = AnchorEnrollment::factory()->create();
    $bearer = $enrollment->uuid.'.'.$enrollment->secret;

    $this->actingAs(admin())
        ->deleteJson("/api/admin/anchors/enrollments/{$enrollment->id}")
        ->assertNoContent();

    // Deleting the row is the whole remediation: the credential stops
    // resolving, so it can neither heartbeat nor open anything.
    $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
        ->postJson('/api/anchor/heartbeat', [
            'version' => '0.1.0',
            'mode' => 'agent',
            'protocol' => ['min' => 1, 'max' => 1],
            'capabilities' => [],
        ])
        ->assertUnauthorized();

    expect(AuditLog::query()->forEvent(AuditEvent::ADMIN_ANCHOR_REJECTED)->count())->toBe(1);
});

it('lets a waiting machine keep heartbeating while it waits', function () {
    $enrollment = AnchorEnrollment::factory()->create(['last_seen_at' => now()->subHour()]);

    // Its credential has to survive the move from the queue into a node, so it
    // is valid the whole time -- approval must not require a re-enrollment.
    $this->withHeaders(['Authorization' => "Bearer {$enrollment->uuid}.{$enrollment->secret}"])
        ->postJson('/api/anchor/heartbeat', [
            'version' => '0.2.0',
            'mode' => 'agent',
            'protocol' => ['min' => 1, 'max' => 1],
            'capabilities' => ['console.qemu.vnc'],
        ])
        ->assertNoContent();

    expect($enrollment->refresh()->version)->toBe('0.2.0');
});

it('keeps the agent credential working across approval', function () {
    $enrollment = AnchorEnrollment::factory()->create();
    $bearer = $enrollment->uuid.'.'.$enrollment->secret;

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/enrollments/{$enrollment->id}/approve", approvalPayload())
        ->assertCreated();

    // Same bearer, different table. This is the reason promotion carries the
    // uuid and secret rather than minting new ones.
    $this->withHeaders(['Authorization' => "Bearer {$bearer}"])
        ->postJson('/api/anchor/heartbeat', [
            'version' => '0.3.0',
            'mode' => 'agent',
            'protocol' => ['min' => 1, 'max' => 1],
            'capabilities' => ['console.qemu.vnc'],
        ])
        ->assertNoContent();

    expect(Node::sole()->agent_version)->toBe('0.3.0');
});

it('records the approval against the node it created', function () {
    $enrollment = AnchorEnrollment::factory()->create();

    $this->actingAs(admin())
        ->postJson("/api/admin/anchors/enrollments/{$enrollment->id}/approve", approvalPayload())
        ->assertCreated();

    $log = AuditLog::query()->forEvent(AuditEvent::ADMIN_ANCHOR_APPROVED)->sole();

    expect($log->subject->is(Node::sole()))->toBeTrue()
        ->and($log->properties['hostname'])->toBe('pve-new.example.com');
});

it('is not something a non-admin can do', function () {
    $enrollment = AnchorEnrollment::factory()->create();

    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->postJson("/api/admin/anchors/enrollments/{$enrollment->id}/approve", approvalPayload())
        ->assertForbidden();

    expect(Node::count())->toBe(0);
});
