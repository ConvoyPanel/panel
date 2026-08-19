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
    $this->storage = Storage::factory()->create(['stores_kvm' => true]);
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

it('lists ISOs on the storage', function () {
    ISO::factory()->create([
        'storage_id' => $this->storage->id,
        'name' => 'debian-12',
        'size' => 700 * 1024 * 1024,
    ]);

    $row = $this->actingAs($this->admin)
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertOk()
        ->json('data.isos.0');

    expect($row['name'])->toBe('debian-12')
        ->and($row['size'])->toBe(700 * 1024 * 1024);
});

it('requires an admin user', function () {
    $this->actingAs(User::factory()->create(['root_admin' => false]))
        ->getJson("/api/admin/storages/{$this->storage->id}/consumers")
        ->assertForbidden();
});
