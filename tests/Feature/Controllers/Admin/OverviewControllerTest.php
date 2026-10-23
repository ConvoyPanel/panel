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
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // OverviewService caches its payload for 15s. Whether that survives between
    // cases depends on the ambient cache driver -- an array store is per-process
    // and hides the problem, a shared redis does not -- so pin it either way.
    Cache::flush();
});

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

it('names the servers and backups behind each attention row', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $location = Location::factory()->create();
    $node = Node::factory()->for($location)->create(['name' => 'pve-1']);

    $failed = Server::factory()->for($node)->for($admin)->create([
        'name' => 'broken-install',
        'status' => Status::INSTALL_FAILED->value,
    ]);
    $healthy = Server::factory()->for($node)->for($admin)->create([
        'name' => 'nightly-host',
        'status' => null,
    ]);
    Backup::factory()->for($healthy)->create([
        'name' => 'nightly',
        'is_successful' => false,
        'completed_at' => now(),
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        // Each row carries the route key its own destination takes, so a click
        // lands on the record rather than on the unfiltered server list. Every
        // subject is keyed by the owning server's short uuid, which is what both
        // the admin and client server routes bind on -- the primary key resolves
        // to nothing.
        ->assertJsonPath('data.attention.failed_servers.0.id', $failed->uuid_short)
        ->assertJsonPath('data.attention.failed_servers.0.label', 'broken-install')
        ->assertJsonPath('data.attention.failed_servers.0.detail', 'Installation failed on pve-1')
        ->assertJsonPath('data.attention.failed_backups.0.id', $healthy->uuid_short)
        ->assertJsonPath('data.attention.failed_backups.0.label', 'nightly');
});

it('leaves a suspended server off the card entirely', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();
    Server::factory()->for($node)->for($admin)->create([
        'status' => Status::SUSPENDED->value,
    ]);

    // A suspension is deliberate, so it belongs in the server-state counts and
    // nowhere near a list of things that need fixing.
    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.servers.suspended', 1)
        ->assertJsonPath('data.attention.failed_servers', [])
        ->assertJsonMissingPath('data.attention.suspended_servers');
});

it('keys attention subjects by something the server route can actually resolve', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();
    Server::factory()->for($node)->for($admin)->create([
        'status' => Status::INSTALL_FAILED->value,
    ]);

    $id = $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->json('data.attention.failed_servers.0.id');

    // The card links at /admin/servers/{id}, so whatever the endpoint hands back
    // has to be the key that page's own request binds on. The primary key is not
    // it: RouteServiceProvider reads a non-8-character value as a uuid, and the
    // page dies with "No query results for model [Convoy\Models\Server]".
    $this->actingAs($admin)->getJson("/api/admin/servers/{$id}")->assertOk();
});

it('separates a deletion failure from an install failure in the detail', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create(['name' => 'pve-2']);

    Server::factory()->for($node)->for($admin)->create([
        'status' => Status::DELETION_FAILED->value,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.attention.failed_servers.0.detail', 'Deletion failed on pve-2');
});

it('leaves the attention groups empty when nothing is wrong', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();
    Server::factory()->for($node)->for($admin)->create(['status' => null]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.attention.failed_servers', [])
        ->assertJsonPath('data.attention.failed_backups', []);
});

it('caps each attention group and leaves the count to say how many there really are', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();

    Server::factory()->count(30)->for($node)->for($admin)->create([
        'status' => Status::INSTALL_FAILED->value,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonCount(25, 'data.attention.failed_servers')
        ->assertJsonPath('data.summary.failed_servers', 30);
});
