<?php

use Convoy\Enums\Server\Status;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;

it('returns overview metrics for admins', function () {
    $admin = User::factory()->create([
        'root_admin' => true,
    ]);
    $location = Location::factory()->create();
    $node = Node::factory()->for($location)->create([
        'memory' => 64 * 1024 * 1024 * 1024,
        'disk' => 128 * 1024 * 1024 * 1024,
    ]);

    $server = Server::factory()->for($node)->for($admin)->create([
        'status' => null,
        'memory' => 8 * 1024 * 1024 * 1024,
        'disk' => 32 * 1024 * 1024 * 1024,
    ]);
    Server::factory()->for($node)->for($admin)->create([
        'status' => Status::INSTALL_FAILED->value,
        'memory' => 4 * 1024 * 1024 * 1024,
        'disk' => 16 * 1024 * 1024 * 1024,
    ]);

    $pool = AddressPool::factory()->create();
    Address::factory()->create([
        'address_pool_id' => $pool->id,
        'server_id' => $server->id,
    ]);
    Address::factory()->create([
        'address_pool_id' => $pool->id,
        'server_id' => null,
    ]);

    Backup::factory()->for($server)->create([
        'is_successful' => true,
        'completed_at' => now(),
    ]);
    Backup::factory()->for($server)->create([
        'is_successful' => false,
        'completed_at' => now(),
    ]);

    ISO::factory()->for($node)->create([
        'is_successful' => false,
    ]);

    $response = $this->actingAs($admin)->getJson('/api/admin/overview');

    $response->assertOk()
        ->assertJsonPath('data.update.status', 'canary')
        ->assertJsonPath('data.update.current_version', 'canary')
        ->assertJsonPath('data.update.is_outdated', false)
        ->assertJsonPath('data.summary.servers', 2)
        ->assertJsonPath('data.summary.nodes', 1)
        ->assertJsonPath('data.summary.failed_servers', 1)
        ->assertJsonPath('data.servers.ready', 1)
        ->assertJsonPath('data.servers.failed', 1)
        ->assertJsonPath('data.addresses.total', 2)
        ->assertJsonPath('data.addresses.assigned', 1)
        ->assertJsonPath('data.backups.total', 2)
        ->assertJsonPath('data.backups.successful', 1)
        ->assertJsonPath('data.backups.failed', 1)
        ->assertJsonPath('data.isos.pending', 1)
        ->assertJsonPath('data.nodes.0.servers', 2)
        ->assertJsonPath('data.capacity.memory.allocated', 12 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.capacity.disk.allocated', 48 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.nodes.0.memory.allocated', 12 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.nodes.0.disk.allocated', 48 * 1024 * 1024 * 1024);
});

it('buckets restoring servers across both restore statuses', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $location = Location::factory()->create();
    $node = Node::factory()->for($location)->create();

    Server::factory()->for($node)->for($admin)->create([
        'status' => Status::RESTORING_BACKUP->value,
    ]);
    Server::factory()->for($node)->for($admin)->create([
        'status' => Status::RESTORING_SNAPSHOT->value,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.servers.restoring', 2);
});

it('requires an admin user', function () {
    $user = User::factory()->create([
        'root_admin' => false,
    ]);

    $this->actingAs($user)->getJson('/api/admin/overview')
        ->assertForbidden();
});
