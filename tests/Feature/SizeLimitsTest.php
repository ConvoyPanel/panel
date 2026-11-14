<?php

use Convoy\Casts\MebibytesToAndFromBytes;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;

// Sizes are stored in whole mebibytes. Past 2,147,483,647 MiB a value no
// longer fits 5.x's Postgres columns, and past twice that not even 4.x's own:
// MySQL rejected the write and the request ended in a 500.
beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->node = Node::factory()->for(Location::factory())->create();
});

it('stores the largest size it accepts', function () {
    $server = Server::factory()->for($this->node)->for(User::factory())->create([
        'disk' => MebibytesToAndFromBytes::MAX_BYTES,
    ]);

    expect($server->fresh()->disk)->toBe(MebibytesToAndFromBytes::MAX_BYTES)
        ->and($server->getRawOriginal('disk'))->toBe(2147483647);
});

it('rejects a server larger than that', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/servers', [
        'name' => 'too big',
        'user_id' => User::factory()->create()->id,
        'node_id' => $this->node->id,
        'vmid' => 4242,
        'hostname' => 'big.example.com',
        'limits' => [
            'cpu' => 1,
            'memory' => MebibytesToAndFromBytes::MAX_BYTES + 1048576,
            'disk' => MebibytesToAndFromBytes::MAX_BYTES + 1048576,
            'snapshots' => null,
            'backups' => null,
            'bandwidth' => null,
            'address_ids' => [],
        ],
        'should_create_server' => false,
        'start_on_completion' => false,
    ])->assertUnprocessable()->assertJsonValidationErrors(['limits.memory', 'limits.disk']);
});

it('rejects a bandwidth limit or usage larger than that', function () {
    $server = Server::factory()->for($this->node)->for(User::factory())->create();

    $this->actingAs($this->admin)->patchJson("/api/admin/servers/{$server->uuid}/settings/build", [
        'cpu' => 1,
        'memory' => 1024 * 1048576,
        'disk' => 10 * 1024 * 1048576,
        'address_ids' => [],
        'snapshot_limit' => null,
        'backup_limit' => null,
        'bandwidth_limit' => MebibytesToAndFromBytes::MAX_BYTES + 1048576,
        'bandwidth_usage' => MebibytesToAndFromBytes::MAX_BYTES + 1048576,
    ])->assertUnprocessable()->assertJsonValidationErrors(['bandwidth_limit', 'bandwidth_usage']);
});

it('rejects a node larger than that', function () {
    $this->actingAs($this->admin)->postJson('/api/admin/nodes', [
        'location_id' => $this->node->location_id,
        'name' => 'Big Node',
        'cluster' => 'proxmox',
        'fqdn' => 'example.com',
        'token_id' => 'test-token',
        'secret' => 'test-secret',
        'port' => 8006,
        'memory' => MebibytesToAndFromBytes::MAX_BYTES + 1048576,
        'memory_overallocate' => 0,
        'disk' => MebibytesToAndFromBytes::MAX_BYTES + 1048576,
        'disk_overallocate' => 0,
        'vm_storage' => 'local-lvm',
        'backup_storage' => 'local-lvm',
        'iso_storage' => 'local-lvm',
        'network' => 'vmbr0',
    ])->assertUnprocessable()->assertJsonValidationErrors(['memory', 'disk']);
});
