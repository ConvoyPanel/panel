<?php

use App\Enums\Server\ServerStatus;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Backup;
use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The metrics are cached; keep each case isolated from the others.
    Cache::flush();
});

it('returns overview metrics for admins', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $location = Location::factory()->create();
    $node = Node::factory()->for($location)->create(['memory' => 64 * 1024 * 1024 * 1024]);

    // One VM-disk storage with a known size, attached to the node; both servers live on
    // it, so the fleet storage total is deterministic. (Incidental storages the backup/ISO
    // factories spin up aren't node-attached, so they're excluded.)
    $storage = Storage::factory()->create([
        'size' => 500 * 1024 * 1024 * 1024,
        'stores_kvm' => true,
    ]);
    $node->storages()->attach($storage);

    $ready = Server::factory()->for($node)->for($admin)->create([
        'storage_id' => $storage->id,
        'status' => ServerStatus::READY->value,
        'memory' => 8 * 1024 * 1024 * 1024,
        'disk' => 32 * 1024 * 1024 * 1024,
    ]);
    Server::factory()->for($node)->for($admin)->create([
        'storage_id' => $storage->id,
        'status' => ServerStatus::INSTALL_FAILED->value,
        'memory' => 4 * 1024 * 1024 * 1024,
        'disk' => 16 * 1024 * 1024 * 1024,
    ]);

    $group = AddressBlockGroup::factory()->create();
    $block = AddressBlock::create([
        'address_block_group_id' => $group->id,
        'name' => 'block',
        'version' => 'ipv4',
        'base_ip' => '10.0.0.0',
        'gateway' => '10.0.0.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 24,
    ]);
    Address::create(['ip' => '10.0.0.5', 'prefix_length' => 24, 'address_block_id' => $block->id, 'server_id' => $ready->id]);
    Address::create(['ip' => '10.0.0.6', 'prefix_length' => 24, 'address_block_id' => $block->id, 'server_id' => null]);

    Backup::factory()->for($ready)->create(['completed_at' => now(), 'errors' => null]);
    Backup::factory()->for($ready)->create(['completed_at' => now(), 'errors' => 'boom']);

    // An ISO on a non-VM-disk storage, so it doesn't skew the storage total.
    ISO::factory()->create([
        'storage_id' => Storage::factory()->create(['stores_kvm' => false, 'stores_iso' => true])->id,
        'is_successful' => false,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.summary.servers', 2)
        ->assertJsonPath('data.summary.nodes', 1)
        ->assertJsonPath('data.summary.failedServers', 1)
        ->assertJsonPath('data.servers.ready', 1)
        ->assertJsonPath('data.servers.failed', 1)
        // DB stores MiB (StorageSizeCast); the endpoint reports bytes.
        ->assertJsonPath('data.memory.allocated', 12 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.memory.total', 64 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.storage.allocated', 48 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.storage.total', 500 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.addresses.pools', 1)
        ->assertJsonPath('data.addresses.total', 2)
        ->assertJsonPath('data.addresses.assigned', 1)
        ->assertJsonPath('data.addresses.available', 1)
        ->assertJsonPath('data.backups.total', 2)
        ->assertJsonPath('data.backups.successful', 1)
        ->assertJsonPath('data.backups.failed', 1)
        ->assertJsonPath('data.isos.total', 1)
        ->assertJsonPath('data.isos.pending', 1)
        ->assertJsonPath('data.nodes.0.servers', 2)
        ->assertJsonPath('data.nodes.0.memory.allocated', 12 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.nodes.0.memory.total', 64 * 1024 * 1024 * 1024);
});

it('counts servers restoring from a backup', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();

    Server::factory()->for($node)->for($admin)->create([
        'status' => ServerStatus::RESTORING_BACKUP->value,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.servers.restoring', 1);
});

it('requires an admin user', function () {
    $user = User::factory()->create(['root_admin' => false]);

    $this->actingAs($user)->getJson('/api/admin/overview')
        ->assertForbidden();
});
