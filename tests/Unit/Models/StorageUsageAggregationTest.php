<?php

use App\Models\Server;
use App\Models\ServerDisk;
use App\Models\Storage;

const GIB = 1024 * 1024 * 1024;

it('sums disk usage from server_disks per storage, spanning multiple storages', function () {
    $storageA = Storage::factory()->create();
    $storageB = Storage::factory()->create();
    $server = Server::factory()->create();

    // Primary 20 GiB on A, secondary 10 GiB on B — one server, two storages.
    ServerDisk::factory()->for($server)->for($storageA)->create([
        'size' => 20 * GIB,
        'is_primary' => true,
    ]);
    ServerDisk::factory()->for($server)->for($storageB)->secondary()->create([
        'size' => 10 * GIB,
    ]);

    $a = Storage::withUsageSums()->find($storageA->id);
    $b = Storage::withUsageSums()->find($storageB->id);

    // server_usage is returned in bytes.
    expect($a->server_usage)->toBe(20 * GIB);
    expect($b->server_usage)->toBe(10 * GIB);
});

it('adds multiple disks on the same storage', function () {
    $storage = Storage::factory()->create();
    $server = Server::factory()->create();

    ServerDisk::factory()->for($server)->for($storage)->create(['size' => 20 * GIB, 'is_primary' => true]);
    ServerDisk::factory()->for($server)->for($storage)->secondary()->create([
        'size' => 5 * GIB,
        'interface' => 'scsi1',
    ]);

    $loaded = Storage::withUsageSums()->find($storage->id);

    expect($loaded->server_usage)->toBe(25 * GIB);
});

it('exposes primaryDisk and disks relations on the server', function () {
    $storage = Storage::factory()->create();
    $server = Server::factory()->create();

    ServerDisk::factory()->for($server)->for($storage)->create(['size' => 20 * GIB, 'is_primary' => true]);
    ServerDisk::factory()->for($server)->for($storage)->secondary()->create([
        'size' => 5 * GIB,
        'interface' => 'scsi1',
    ]);

    expect($server->disks()->count())->toBe(2);
    expect($server->primaryDisk->is_primary)->toBeTrue();
    expect($server->primaryDisk->size)->toBe(20 * GIB);
});
