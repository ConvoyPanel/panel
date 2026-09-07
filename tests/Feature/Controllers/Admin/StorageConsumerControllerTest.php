<?php

use App\Models\Backup;
use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\ServerDisk;
use App\Models\Storage;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->node = Node::factory()->for(Location::factory())->create();
    $this->storage = Storage::factory()->create(['pve_content' => 'images']);
    $this->node->storages()->attach($this->storage);
});

it('lists what is occupying the storage, largest first', function () {
    $small = Server::factory()->for($this->node)->for($this->admin)->create(['name' => 'small']);
    $large = Server::factory()->for($this->node)->for($this->admin)->create(['name' => 'large']);

    ServerDisk::factory()->for($small)->create(['storage_id' => $this->storage->id, 'size' => 1024 * 1024 * 1024]);
    ServerDisk::factory()->for($large)->create(['storage_id' => $this->storage->id, 'size' => 8 * 1024 * 1024 * 1024]);

    $names = $this->actingAs($this->admin)
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertOk()
        ->json('data.servers.*.name');

    expect($names)->toBe(['large', 'small']);
});

it('sums a server that has several disks here rather than listing it twice', function () {
    $server = Server::factory()->for($this->node)->for($this->admin)->create(['name' => 'two-disks']);
    ServerDisk::factory()->for($server)->create(['storage_id' => $this->storage->id, 'size' => 1024 * 1024 * 1024]);
    ServerDisk::factory()->for($server)->create(['storage_id' => $this->storage->id, 'size' => 2 * 1024 * 1024 * 1024]);

    $rows = $this->actingAs($this->admin)
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertOk()
        ->json('data.servers');

    expect($rows)->toHaveCount(1)
        // MiB in the database, bytes on the wire, like every other capacity figure.
        ->and($rows[0]['size'])->toBe(3 * 1024 * 1024 * 1024);
});

it('marks a locked backup as not deletable', function () {
    $server = Server::factory()->for($this->node)->for($this->admin)->create();
    Backup::factory()->for($server)->create([
        'storage_id' => $this->storage->id,
        'is_locked' => true,
        'size' => 512 * 1024 * 1024,
    ]);

    $row = $this->actingAs($this->admin)
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertOk()
        ->json('data.backups.0');

    expect($row['deletable'])->toBeFalse();
});

it('does not attribute ISOs to a storage', function () {
    // An ISO belongs to the library, not to a storage: a node fetches one when
    // someone mounts it, and that copy is a cache PVE owns. Reporting it here
    // would be claiming an allocation the panel never made.
    ISO::factory()->create(['name' => 'debian-12', 'size' => 700 * 1024 * 1024]);

    $consumers = $this->actingAs($this->admin)
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertOk()
        ->json('data');

    expect($consumers)->not->toHaveKey('isos');
});

it('requires an admin user', function () {
    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertForbidden();
});
