<?php

use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;
use App\Models\Vlan;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        '*/api2/json/cluster/nextid*' => Http::response(['data' => 12345]),
        '*/api2/json/nodes/*/storage' => Http::response(['data' => []]),
    ]);

    $this->user = User::factory()->create(['root_admin' => true]);
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    $this->storage = Storage::factory()->create();
    $this->node->storages()->attach($this->storage);

    $this->trunk = NetworkInterface::factory()->for($this->node)->trunk(100)->create();
});

/**
 * Deferred-OS path: no template build and no address allocation, so a create
 * reaches validation without needing Proxmox to answer for anything else.
 */
$payload = function ($test, array $limits) {
    return [
        'user_id' => $test->user->id,
        'node_id' => $test->node->id,
        'storage_id' => $test->storage->id,
        'vmid' => 12345,
        'hostname' => 'test-host',
        'name' => 'Test Server',
        'description' => null,
        'deferred_os_selection' => true,
        'should_create_vm' => false,
        'start_on_completion' => false,
        'account_password' => null,
        'template_uuid' => null,
        'limits' => array_merge([
            'cpu' => 2,
            'memory' => 2 * 1024 * 1024 * 1024,
            'disk' => 20 * 1024 * 1024 * 1024,
            'bandwidth' => 100 * 1024 * 1024 * 1024,
            'addresses_ipv4_count' => 0,
            'addresses_ipv6_count' => 0,
            'backups' => ['count' => 16, 'size' => 100 * 1024 * 1024 * 1024],
        ], $limits),
    ];
};

it('rejects a server on a VLAN that was never declared', function () use ($payload) {
    $this->actingAs($this->user)
        ->postJson('/api/admin/servers', $payload($this, [
            'network_interface_id' => $this->trunk->id,
            'vlan_tag' => 999,
        ]))
        ->assertJsonValidationErrorFor('limits.vlan_tag');
});

it('accepts a server on a declared VLAN', function () use ($payload) {
    Vlan::factory()->for($this->trunk, 'networkInterface')->create(['tag' => 205]);

    $this->actingAs($this->user)
        ->postJson('/api/admin/servers', $payload($this, [
            'network_interface_id' => $this->trunk->id,
            'vlan_tag' => 205,
        ]))
        ->assertCreated();

    $this->assertDatabaseHas('servers', ['vlan_tag' => 205]);
});

it('rejects a VLAN declared on a different bridge', function () use ($payload) {
    $other = NetworkInterface::factory()->for($this->node)->trunk()->create();
    Vlan::factory()->for($other, 'networkInterface')->create(['tag' => 205]);

    $this->actingAs($this->user)
        ->postJson('/api/admin/servers', $payload($this, [
            'network_interface_id' => $this->trunk->id,
            'vlan_tag' => 205,
        ]))
        ->assertJsonValidationErrorFor('limits.vlan_tag');
});

it('still allows no VLAN at all', function () use ($payload) {
    $this->actingAs($this->user)
        ->postJson('/api/admin/servers', $payload($this, [
            'network_interface_id' => $this->trunk->id,
            'vlan_tag' => null,
        ]))
        ->assertCreated();
});

/**
 * The tag can sit untouched in the payload while the interface moves under it,
 * so the build endpoint has to re-check an unchanged tag against the new bridge.
 */
it('rejects moving a server to a bridge where its tag is not declared', function () {
    Vlan::factory()->for($this->trunk, 'networkInterface')->create(['tag' => 205]);

    $server = Server::factory()->for($this->node)->for($this->user)->for($this->storage)->create([
        'network_interface_id' => $this->trunk->id,
        'vlan_tag' => 205,
    ]);

    $bare = NetworkInterface::factory()->for($this->node)->trunk()->create();

    $this->actingAs($this->user)
        ->patchJson("/api/admin/servers/{$server->uuid}/settings/build", [
            'cpu' => $server->cpu,
            'memory' => $server->memory,
            'disk' => $server->disk,
            'network_interface_id' => $bare->id,
            'bandwidth_limit' => $server->bandwidth_limit,
            'bandwidth_usage' => 0,
            'backup_count_limit' => $server->backup_count_limit,
            'backup_size_limit' => $server->backup_size_limit,
        ])
        ->assertJsonValidationErrorFor('vlan_tag');
});
