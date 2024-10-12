<?php


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

it('can fetch nodes', function () {
    $response = $this->actingAs($this->user)->getJson('/api/admin/nodes');

    $response->assertOk();
});

it('can fetch a node', function () {
    $response = $this->actingAs($this->user)->getJson("/api/admin/nodes/{$this->node->id}");

    $response->assertOk();
});

it('can create a node', function () {
    $response = $this->actingAs($this->user)->postJson('/api/admin/nodes', [
        'location_id' => $this->location->id,
        'name' => 'Test Node',
        'cluster' => 'proxmox',
        'fqdn' => 'example.com',
        'token_id' => 'test-token',
        'secret' => 'test-secret',
        'port' => 8006,
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB,
        'memory_overallocate' => 0,
        'disk' => 512 * 1024 * 1024 * 1024, // 512GB,
        'disk_overallocate' => 0,
        'vm_storage' => 'local-lvm',
        'backup_storage' => 'local-lvm',
        'iso_storage' => 'local-lvm',
        'network' => 'vmbr0',
    ]);

    $response->assertOk();
});

it('can update a node', function () {
    $response = $this->actingAs($this->user)->putJson("/api/admin/nodes/{$this->node->id}", [
        'location_id' => $this->location->id,
        'name' => 'Test Node',
        'cluster' => 'proxmox',
        'fqdn' => 'example.com',
        'token_id' => 'test-token',
        'secret' => 'test-secret',
        'port' => 8006,
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB,
        'memory_overallocate' => 0,
        'disk' => 512 * 1024 * 1024 * 1024, // 512GB,
        'disk_overallocate' => 0,
        'vm_storage' => 'local-lvm',
        'backup_storage' => 'local-lvm',
        'iso_storage' => 'local-lvm',
        'network' => 'vmbr0',
    ]);

    $response->assertOk();
});

it("can't downsize without over-allocating", function () {
    $node = Node::factory()->for($this->location)->create([
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB,
        'disk' => 512 * 1024 * 1024 * 1024, // 512GB,
    ]);

    Server::factory()->for($node)->for($this->user)->create([
        'memory' => 32 * 1024 * 1024 * 1024, // 32GB,
        'disk' => 256 * 1024 * 1024 * 1024, // 256GB,
    ]);

    $response = $this->actingAs($this->user)->putJson("/api/admin/nodes/{$node->id}", [
        'location_id' => $this->location->id,
        'name' => 'Test Node',
        'cluster' => 'proxmox',
        'fqdn' => 'example.com',
        'token_id' => 'test-token',
        'secret' => 'test-secret',
        'port' => 8006,
        'memory' => 16 * 1024 * 1024 * 1024, // 16GB,
        'memory_overallocate' => 0,
        'disk' => 128 * 1024 * 1024 * 1024, // 128GB,
        'disk_overallocate' => 0,
        'vm_storage' => 'local-lvm',
        'backup_storage' => 'local-lvm',
        'iso_storage' => 'local-lvm',
        'network' => 'vmbr0',
    ]);

    $response->assertStatus(422);
});

it('can update node without false positive overallocation', function () {
    $node = Node::factory()->for($this->location)->create([
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB,
        'disk' => 512 * 1024 * 1024 * 1024, // 512GB,
    ]);

    Server::factory()->for($node)->for($this->user)->create([
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB,
        'disk' => 256 * 1024 * 1024 * 1024, // 256GB,
    ]);

    $response = $this->actingAs($this->user)->putJson("/api/admin/nodes/{$node->id}", [
        'location_id' => $this->location->id,
        'name' => 'New name',
        'cluster' => 'proxmox',
        'fqdn' => 'example.com',
        'token_id' => 'test-token',
        'secret' => 'test-secret',
        'port' => 8006,
        'memory' => 64 * 1024 * 1024 * 1024, // 64GB,
        'memory_overallocate' => 0,
        'disk' => 512 * 1024 * 1024 * 1024, // 512GB,
        'disk_overallocate' => 0,
        'vm_storage' => 'local-lvm',
        'backup_storage' => 'local-lvm',
        'iso_storage' => 'local-lvm',
        'network' => 'vmbr0',
    ]);

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
