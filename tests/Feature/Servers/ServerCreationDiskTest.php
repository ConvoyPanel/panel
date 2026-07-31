<?php

use App\Enums\Server\ServerLifecycle;
use App\Models\Location;
use App\Models\NetworkInterface;
use App\Models\Node;
use App\Models\Storage;
use App\Models\User;
use App\Services\Servers\ServerCreationService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->location = Location::factory()->create();
    $this->node = Node::factory()->for($this->location)->create();
    $this->storage = Storage::factory()->create();
    $this->node->storages()->attach($this->storage);
});

/** @return array<string, mixed> */
function creationData(int $userId, int $nodeId, int $storageId): array
{
    // Deferred-OS path: no template build, no Proxmox calls (explicit vmid, no
    // address allocation), so this exercises the DB write in isolation.
    return [
        'user_id' => $userId,
        'node_id' => $nodeId,
        'storage_id' => $storageId,
        'vmid' => 12345,
        'hostname' => 'test-host',
        'name' => 'Test Server',
        'description' => null,
        'deferred_os_selection' => true,
        'should_create_vm' => false,
        'start_on_completion' => false,
        'account_password' => null,
        'template_uuid' => null,
        'limits' => [
            'cpu' => 2,
            'memory' => 2 * 1024 * 1024 * 1024,
            'disk' => 20 * 1024 * 1024 * 1024,
            'bandwidth' => -1,
            'addresses_ipv4_count' => 0,
            'addresses_ipv6_count' => 0,
            'backups' => ['count' => -1, 'size' => -1],
        ],
    ];
}

it('creates a server with its uuid populated (guarded-field regression)', function () {
    $server = app(ServerCreationService::class)->handle(
        creationData($this->user->id, $this->node->id, $this->storage->id),
    );

    expect($server->uuid)->not->toBeNull();
    expect($server->uuid_short)->toBe(substr($server->uuid, 0, 8));
    expect($server->lifecycle)->toBe(ServerLifecycle::DEFERRED_OS_SELECTION);
});

it('persists the speed cap and anchors the bandwidth reset day', function () {
    Carbon::setTestNow('2026-04-09 12:00:00');

    $data = creationData($this->user->id, $this->node->id, $this->storage->id);
    $data['limits']['speed_limit'] = 100_000_000; // 100 MB/s in bytes/s

    $server = app(ServerCreationService::class)->handle($data);

    expect($server->speed_limit)->toBe(100_000_000)
        ->and($server->bandwidth_reset_day)->toBe(9);

    Carbon::setTestNow();
});

it('creates secondary disk rows from limits.disks', function () {
    $secondaryStorage = Storage::factory()->create();
    $this->node->storages()->attach($secondaryStorage);

    $data = creationData($this->user->id, $this->node->id, $this->storage->id);
    $data['limits']['disks'] = [
        ['storage_id' => $secondaryStorage->id, 'size' => 50 * 1024 * 1024 * 1024],
    ];

    $server = app(ServerCreationService::class)->handle($data);

    expect($server->disks()->count())->toBe(2);

    $secondary = $server->disks()->where('is_primary', false)->first();
    expect($secondary->storage_id)->toBe($secondaryStorage->id);
    expect($secondary->size)->toBe(50 * 1024 * 1024 * 1024);
    expect($secondary->interface)->toBeNull(); // assigned at build time
    expect($secondary->disk_index)->toBe(1);
});

it('mirrors the primary disk into server_disks on creation', function () {
    $server = app(ServerCreationService::class)->handle(
        creationData($this->user->id, $this->node->id, $this->storage->id),
    );

    $primary = $server->primaryDisk;
    expect($primary)->not->toBeNull();
    expect($primary->is_primary)->toBeTrue();
    expect($primary->storage_id)->toBe($this->storage->id);
    expect($primary->size)->toBe(20 * 1024 * 1024 * 1024);
    expect($server->disks()->count())->toBe(1);

    // And it flows through to the storage's disk-usage aggregation.
    $loaded = Storage::withUsageSums()->find($this->storage->id);
    expect($loaded->server_usage)->toBe(20 * 1024 * 1024 * 1024);
});

it('persists the selected network interface and VLAN override on creation', function () {
    $interface = NetworkInterface::create([
        'node_id' => $this->node->id,
        'name' => 'vmbr0',
        'is_vlan_aware' => true,
        'vlan_tag' => 42,
    ]);

    $data = creationData($this->user->id, $this->node->id, $this->storage->id);
    $data['limits']['network_interface_id'] = $interface->id;
    $data['limits']['vlan_tag'] = 123;

    $server = app(ServerCreationService::class)->handle($data);

    expect($server->network_interface_id)->toBe($interface->id);
    expect($server->vlan_tag)->toBe(123);
});

it('leaves the speed limit null when none is given', function () {
    // Null = uncapped. The admin create form omits the key when the field is
    // blank rather than sending a 0, which would cap every NIC at zero.
    $server = app(ServerCreationService::class)->handle(
        creationData($this->user->id, $this->node->id, $this->storage->id),
    );

    expect($server->speed_limit)->toBeNull();
});

it('creates an addressless deferred server through the API', function () {
    Http::fake([
        '*/api2/json/cluster/nextid*' => Http::response(['data' => 12345]),
        '*/api2/json/nodes/*/storage' => Http::response(['data' => []]),
    ]);
    $this->user->update(['root_admin' => true]);
    $interface = NetworkInterface::create([
        'node_id' => $this->node->id,
        'name' => 'vmbr0',
        'is_vlan_aware' => false,
        'vlan_tag' => null,
    ]);

    $data = creationData($this->user->id, $this->node->id, $this->storage->id);
    $data['limits']['network_interface_id'] = $interface->id;
    $data['limits']['speed_limit'] = 12_500_000;

    $this->actingAs($this->user)
        ->postJson('/api/admin/servers', $data)
        ->assertCreated();

    $this->assertDatabaseHas('servers', [
        'name' => 'Test Server',
        'lifecycle' => ServerLifecycle::DEFERRED_OS_SELECTION->value,
        'speed_limit' => 12_500_000,
        'network_interface_id' => $interface->id,
    ]);
});
