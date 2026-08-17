<?php

use App\Data\Cluster\NodeResourceData;
use App\Data\Cluster\StorageResourceData;
use App\Enums\Server\Backup\BackupErrorCode;
use App\Enums\Server\ServerLifecycle;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\Backup;
use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;
use App\Services\Nodes\NodeResourceSnapshotCache;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The metrics are cached; keep each case isolated from the others.
    Cache::flush();
});

it('returns overview metrics for admins', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $location = Location::factory()->create();
    $node = Node::factory()->for($location)->create(['memory' => 64 * 1024 * 1024 * 1024]);
    app(NodeResourceSnapshotCache::class)->put($node, new NodeResourceData(
        nodeName: $node->name,
        status: 'online',
        cpuCount: 16,
        cpuUsed: 0.25,
        memoryTotal: 64 * 1024 * 1024 * 1024,
        memoryUsed: 16 * 1024 * 1024 * 1024,
        diskTotal: 256 * 1024 * 1024 * 1024,
        diskUsed: 64 * 1024 * 1024 * 1024,
        uptimeInSeconds: 86400,
    ));

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
        'lifecycle' => ServerLifecycle::READY->value,
        'memory' => 8 * 1024 * 1024 * 1024,
        'disk' => 32 * 1024 * 1024 * 1024,
    ]);
    Server::factory()->for($node)->for($admin)->create([
        'storage_id' => $storage->id,
        'lifecycle' => ServerLifecycle::INSTALL_FAILED->value,
        'memory' => 4 * 1024 * 1024 * 1024,
        'disk' => 16 * 1024 * 1024 * 1024,
    ]);

    $block = AddressBlock::factory()->create([
        'base_ip' => '10.0.0.0',
        'gateway' => '10.0.0.1',
        'prefix_length_from' => 24,
        'prefix_length_to' => 24,
    ]);
    Address::factory()->for($block)->create(['ip' => '10.0.0.5', 'prefix_length' => 24, 'server_id' => $ready->id]);
    Address::factory()->for($block)->create(['ip' => '10.0.0.6', 'prefix_length' => 24]);

    Backup::factory()->for($ready)->create(['completed_at' => now(), 'error_code' => null]);
    Backup::factory()->for($ready)->create(['completed_at' => now(), 'error_code' => BackupErrorCode::OTHER, 'error_message' => 'boom']);

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
        ->assertJsonPath('data.nodes.0.memory.total', 64 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.nodes.0.resources.cpu.percent', 25)
        ->assertJsonPath('data.nodes.0.resources.memory.used', 16 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.nodes.0.resources.memory.percent', 25)
        ->assertJsonPath('data.nodes.0.resources.disk.used', 64 * 1024 * 1024 * 1024)
        ->assertJsonPath('data.nodes.0.resources.disk.percent', 25);

    // The endpoint caches OverviewData; node rows must remain a plain array after cache hydration.
    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.nodes.0.servers', 2);
});

it('counts servers restoring from a backup', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();

    Server::factory()->for($node)->for($admin)->create([
        'lifecycle' => ServerLifecycle::RESTORING_BACKUP->value,
    ]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.servers.restoring', 1);
});

it('exposes each node datastore the poller recorded', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();

    app(NodeResourceSnapshotCache::class)->put(
        $node,
        new NodeResourceData(
            nodeName: $node->name,
            status: 'online',
            cpuCount: 8,
            cpuUsed: 0.1,
            memoryTotal: 100,
            memoryUsed: 10,
            diskTotal: 100,
            diskUsed: 10,
            uptimeInSeconds: 60,
        ),
        collect([
            new StorageResourceData(
                name: 'local',
                nodeName: $node->name,
                used: 25,
                total: 100,
                status: 'available',
                shared: false,
            ),
            new StorageResourceData(
                name: 'nfs-dead',
                nodeName: $node->name,
                used: 0,
                total: 0,
                status: 'unknown',
                shared: true,
            ),
        ]),
    );

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.nodes.0.resources.datastores.0.name', 'local')
        ->assertJsonPath('data.nodes.0.resources.datastores.0.usage.percent', 25)
        ->assertJsonPath('data.nodes.0.resources.datastores.0.online', true)
        ->assertJsonPath('data.nodes.0.resources.datastores.1.name', 'nfs-dead')
        ->assertJsonPath('data.nodes.0.resources.datastores.1.online', false)
        ->assertJsonPath('data.nodes.0.resources.datastores.1.shared', true);
});

it('requires an admin user', function () {
    $user = User::factory()->create(['root_admin' => false]);

    $this->actingAs($user)->getJson('/api/admin/overview')
        ->assertForbidden();
});

it('counts fleet storage from what the poll observed, not what was typed', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $node = Node::factory()->for(Location::factory())->create();

    // Declared 100 GiB, actually 250 GiB. Every other page reads the observed
    // figure; the dashboard disagreeing with them would be the bug.
    $observed = Storage::factory()->create([
        'size' => 100 * 1024 * 1024 * 1024,
        'stores_kvm' => true,
    ]);
    $observed->forceFill([
        'discovered_total' => 250 * 1024 * 1024 * 1024,
        'discovered_used' => 0,
        'discovered_at' => now(),
    ])->save();

    // Never polled, so the declared size is the best answer there is.
    $declaredOnly = Storage::factory()->create([
        'size' => 50 * 1024 * 1024 * 1024,
        'stores_kvm' => true,
    ]);

    $node->storages()->attach([$observed->id, $declaredOnly->id]);

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.storage.total', 300 * 1024 * 1024 * 1024);
});

it('counts a shared pool once however many nodes mount it', function () {
    $admin = User::factory()->create(['root_admin' => true]);
    $location = Location::factory()->create();
    $shared = Storage::factory()->create([
        'size' => 200 * 1024 * 1024 * 1024,
        'stores_kvm' => true,
    ]);

    foreach (range(1, 3) as $ignored) {
        Node::factory()->for($location)->create()->storages()->attach($shared);
    }

    $this->actingAs($admin)->getJson('/api/admin/overview')
        ->assertOk()
        ->assertJsonPath('data.storage.total', 200 * 1024 * 1024 * 1024);
});
