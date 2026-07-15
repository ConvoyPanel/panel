<?php

use App\Enums\Server\OveragePenaltyAction;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'root_admin' => true,
    ]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
});

function nodePayload(array $overrides = []): array
{
    return array_merge([
        'location_id' => test()->location->id,
        'display_name' => 'Test Node',
        'name' => 'test-node',
        'fqdn' => 'example.com',
        'verify_tls' => true,
        'token_id' => 'test-token',
        'token_secret' => 'test-secret',
        'port' => 8006,
        'socket_count' => 2,
        'core_count' => 16,
        'cpu_count' => 32,
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB
        'memory_overallocate' => 0,
    ], $overrides);
}

function liveNodeStatusPayload(): array
{
    return [
        'current-kernel' => [
            'version' => '#1 SMP PREEMPT_DYNAMIC',
            'release' => '6.14.8-2-pve',
            'sysname' => 'Linux',
            'machine' => 'x86_64',
        ],
        'cpuinfo' => [
            'cpus' => 32,
            'sockets' => 1,
            'cores' => 16,
            'model' => 'AMD EPYC',
            'flags' => 'fpu sse',
        ],
        'cpu' => 0.125,
        'loadavg' => ['0.50', '0.75', '1.00'],
        'memory' => [
            'used' => 32_000,
            'free' => 16_000,
            'available' => 24_000,
            'total' => 64_000,
        ],
        'swap' => ['used' => 1_000, 'free' => 3_000, 'total' => 4_000],
        'rootfs' => ['used' => 40_000, 'free' => 60_000, 'avail' => 55_000, 'total' => 100_000],
        'boot-info' => ['mode' => 'efi', 'secureboot' => true],
        'pveversion' => 'pve-manager/9.2.2',
        'uptime' => 90_061,
    ];
}

it('can fetch nodes', function () {
    $response = $this->actingAs($this->user)->getJson('/api/admin/nodes');

    $response->assertOk();
});

it('can fetch a node', function () {
    $response = $this->actingAs($this->user)->getJson("/api/admin/nodes/{$this->node->id}");

    $response->assertOk();
});

it('can fetch live node status', function () {
    Http::fake([
        '*/api2/json/nodes/*/status' => Http::response(['data' => liveNodeStatusPayload()], 200),
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/status",
    );

    $response->assertOk()
        ->assertJsonPath('data.cpuUsage', 0.125)
        ->assertJsonPath('data.loadAverage.1', 0.75)
        ->assertJsonPath('data.memory.available', 24_000)
        ->assertJsonPath('data.rootFilesystem.total', 100_000)
        ->assertJsonPath('data.boot.mode', 'efi')
        ->assertJsonPath('data.uptimeSeconds', 90_061);
});

it('handles optional live node status fields', function () {
    $payload = liveNodeStatusPayload();
    unset(
        $payload['cpuinfo']['flags'],
        $payload['memory']['available'],
        $payload['swap'],
        $payload['boot-info']['secureboot'],
    );

    Http::fake([
        '*/api2/json/nodes/*/status' => Http::response(['data' => $payload], 200),
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}/status",
    );

    $response->assertOk()
        ->assertJsonPath('data.cpu.flags', '')
        ->assertJsonPath('data.memory.available', null)
        ->assertJsonPath('data.swap.total', 0)
        ->assertJsonPath('data.boot.secureBoot', null);
});

it('can create a node', function () {
    $response = $this->actingAs($this->user)->postJson('/api/admin/nodes', nodePayload());

    $response->assertCreated();
});

it('can update a node', function () {
    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$this->node->id}",
        nodePayload(),
    );

    $response->assertOk();
});

it('persists a per-node overage penalty override', function () {
    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$this->node->id}",
        nodePayload(['overage_penalty' => ['action' => 'disconnect', 'rate' => null]]),
    );

    $response->assertOk();

    expect($this->node->refresh()->overage_penalty)->not->toBeNull()
        ->and($this->node->overage_penalty->action)
        ->toBe(OveragePenaltyAction::DISCONNECT);
});

it('clears a per-node overage penalty override back to inherit', function () {
    $this->node->update([
        'overage_penalty' => ['action' => 'throttle', 'rate' => 5_000_000],
    ]);
    expect($this->node->refresh()->overage_penalty)->not->toBeNull();

    // A null (rather than an omitted key) is how the settings UI says "inherit";
    // `sometimes|nullable` must treat it as a clear, not as "leave unchanged".
    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$this->node->id}",
        nodePayload(['overage_penalty' => null]),
    );

    $response->assertOk();
    expect($this->node->refresh()->overage_penalty)->toBeNull();
});

it('exposes the node override and the global default it falls back to', function () {
    $this->node->update([
        'overage_penalty' => ['action' => 'throttle', 'rate' => 5_000_000],
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}",
    );

    // The settings screen needs both: the node's own override, and the global
    // tier it would inherit if the override were cleared (the "effective" hint).
    $response->assertOk()
        ->assertJsonPath('data.overagePenalty.action', 'throttle')
        ->assertJsonPath('data.overagePenalty.rate', 5_000_000)
        ->assertJsonPath('data.defaultOveragePenalty.action', 'throttle')
        ->assertJsonPath('data.defaultOveragePenalty.rate', 1_000_000);
});

it('reports a null override when the node inherits', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/admin/nodes/{$this->node->id}",
    );

    $response->assertOk()
        ->assertJsonPath('data.overagePenalty', null)
        ->assertJsonPath('data.defaultOveragePenalty.action', 'throttle');
});

it("can't downsize memory below what's allocated", function () {
    $node = Node::factory()->for($this->location)->create([
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB
    ]);

    Server::factory()->for($node)->for($this->user)->create([
        'memory' => 32 * 1024 * 1024 * 1024, // 32GB
    ]);

    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$node->id}",
        nodePayload(['memory' => 16 * 1024 * 1024 * 1024]), // 16GB < 32GB allocated
    );

    $response->assertStatus(422);
});

it('can update node without false positive overallocation', function () {
    $node = Node::factory()->for($this->location)->create([
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB
    ]);

    Server::factory()->for($node)->for($this->user)->create([
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB
    ]);

    $response = $this->actingAs($this->user)->putJson(
        "/api/admin/nodes/{$node->id}",
        nodePayload(['name' => 'new-name', 'memory' => 64 * 1024 * 1024 * 1024]),
    );

    $response->assertOk();
});

it('can delete a node', function () {
    $response = $this->actingAs($this->user)->deleteJson("/api/admin/nodes/{$this->node->id}");

    $response->assertNoContent();
});

it("can't delete a node with servers", function () {
    Server::factory()->for($this->node)->for($this->user)->create();

    $response = $this->actingAs($this->user)->deleteJson("/api/admin/nodes/{$this->node->id}");

    $response->assertForbidden();
});
