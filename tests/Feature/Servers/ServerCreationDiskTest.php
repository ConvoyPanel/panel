<?php

use App\Enums\Server\ServerStatus;
use App\Models\Location;
use App\Models\Node;
use App\Models\Storage;
use App\Models\User;
use App\Services\Servers\ServerCreationService;

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
    expect($server->status)->toBe(ServerStatus::DEFERRED_OS_SELECTION);
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
