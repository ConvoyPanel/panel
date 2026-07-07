<?php

use App\Models\ServerDisk;
use App\Models\Storage;
use App\Services\Servers\AllocationService;
use Illuminate\Support\Facades\Http;

const DISK_GIB = 1024 * 1024 * 1024;

it('allocates a secondary disk on the next free scsi slot, with the digest', function () {
    fakeProxmox();
    [, , , $server] = createServerModel();

    $storage = Storage::factory()->create(['name' => 'fast-nvme']);
    ServerDisk::factory()->for($server)->for($storage)->secondary()->create([
        'size' => 50 * DISK_GIB,
    ]);

    app(AllocationService::class)->syncDisks($server);

    // Fixture's disks are sata0 + ide0 (no scsi), so the secondary takes scsi0,
    // allocated via the STORAGE:SIZE_GiB syntax and guarded by the read digest.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && ($request['scsi0'] ?? null) === 'fast-nvme:50'
        && ! empty($request['digest']));

    // The chosen interface is persisted (retry-safe).
    expect($server->disks()->where('is_primary', false)->first()->interface)->toBe('scsi0');
});

it('batches multiple secondary disks into one write on consecutive scsi slots', function () {
    fakeProxmox();
    [, , , $server] = createServerModel();

    $a = Storage::factory()->create(['name' => 'nvme']);
    $b = Storage::factory()->create(['name' => 'hdd']);
    ServerDisk::factory()->for($server)->for($a)->secondary()->create(['size' => 10 * DISK_GIB, 'disk_index' => 1]);
    ServerDisk::factory()->for($server)->for($b)->secondary()->create(['size' => 20 * DISK_GIB, 'disk_index' => 2]);

    app(AllocationService::class)->syncDisks($server);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && ($request['scsi0'] ?? null) === 'nvme:10'
        && ($request['scsi1'] ?? null) === 'hdd:20');
});

it('skips a disk already present on the VM (idempotent)', function () {
    // The disk is already on scsi0 in the live config, and the row records it.
    fakeProxmox(['*/config' => Http::response(
        serverConfigFixture(['scsi0' => 'fast-nvme:vm-1-disk-1,size=50G']),
        200,
    )]);
    [, , , $server] = createServerModel();

    $storage = Storage::factory()->create(['name' => 'fast-nvme']);
    ServerDisk::factory()->for($server)->for($storage)->secondary()->create([
        'size' => 50 * DISK_GIB,
        'interface' => 'scsi0',
    ]);

    app(AllocationService::class)->syncDisks($server);

    // Only the config read happened — no allocation write.
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST');
});

it('does nothing (no config read) when there are no secondary disks', function () {
    fakeProxmox();
    [, , , $server] = createServerModel();

    app(AllocationService::class)->syncDisks($server);

    Http::assertNothingSent();
});
