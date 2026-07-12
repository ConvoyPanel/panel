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

it('can fetch nodes', function () {
    $response = $this->actingAs($this->user)->getJson('/api/admin/nodes');

    $response->assertOk();
});

it('can fetch a node', function () {
    $response = $this->actingAs($this->user)->getJson("/api/admin/nodes/{$this->node->id}");

    $response->assertOk();
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
