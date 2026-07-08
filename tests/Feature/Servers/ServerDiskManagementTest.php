<?php

use App\Models\ServerDisk;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Support\Facades\Http;

const BYTES_PER_GIB = 1024 * 1024 * 1024;

function admin(): User
{
    return User::factory()->create(['root_admin' => true]);
}

/**
 * fakeProxmox + an empty storage-status list so the capacity closures in the
 * disk requests fail open (live free unknown → allow). Capacity *enforcement*
 * is unit-tested in HasSufficientDiskSpaceTest; here we exercise the endpoints.
 */
function fakeProxmoxWithStorage(array $overrides = []): void
{
    fakeProxmox($overrides + ['*/storage' => Http::response(['data' => []], 200)]);
}

it('lists a server\'s disks ordered by index', function () {
    [, , , $server] = createServerModel();
    ServerDisk::factory()->for($server)->create(['is_primary' => true, 'disk_index' => 0]);
    ServerDisk::factory()->for($server)->secondary()->create(['disk_index' => 1]);

    $this->actingAs(admin())
        ->getJson("/api/admin/servers/{$server->uuid}/disks")
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.isPrimary', true)
        ->assertJsonPath('1.isPrimary', false);
});

it('adds a secondary disk and allocates it on Proxmox', function () {
    fakeProxmoxWithStorage();
    [, , $node, $server] = createServerModel();
    $storage = Storage::factory()->create(['name' => 'fast-nvme']);
    $node->storages()->attach($storage);

    $this->actingAs(admin())
        ->postJson("/api/admin/servers/{$server->uuid}/disks", [
            'storage_id' => $storage->id,
            'size' => 50 * BYTES_PER_GIB,
        ])
        ->assertCreated()
        ->assertJsonPath('data.isPrimary', false);

    // A new secondary row exists, allocated on the first free scsi slot (the
    // fixture has no scsi disks) via the STORAGE:SIZE_GiB syntax.
    $disk = $server->disks()->where('is_primary', false)->firstOrFail();
    expect($disk->interface)->toBe('scsi0');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/config')
        && $request->method() === 'POST'
        && ($request['scsi0'] ?? null) === 'fast-nvme:50');
});

it('grows a secondary disk that is already on the VM', function () {
    // The disk lives on scsi1 in the live config and the row records it.
    fakeProxmoxWithStorage(['*/config' => Http::response(
        serverConfigFixture(['scsi1' => 'nvme:vm-1-disk-1,size=10G']),
        200,
    )]);
    [, , , $server] = createServerModel();
    $storage = Storage::factory()->create(['name' => 'nvme']);
    $disk = ServerDisk::factory()->for($server)->for($storage)->secondary()->create([
        'size' => 10 * BYTES_PER_GIB,
        'interface' => 'scsi1',
    ]);

    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}", [
            'size' => 20 * BYTES_PER_GIB,
        ])
        ->assertOk()
        ->assertJsonPath('data.size', 20 * BYTES_PER_GIB);

    // The PVE resize endpoint was hit and the row grew.
    Http::assertSent(fn ($request) => str_contains($request->url(), '/resize'));
    expect((int) $disk->refresh()->size)->toBe(20 * BYTES_PER_GIB);
});

it('updates only the row when a pending (unbuilt) disk is resized', function () {
    fakeProxmoxWithStorage();
    [, , , $server] = createServerModel();
    $disk = ServerDisk::factory()->for($server)->secondary()->create([
        'size' => 10 * BYTES_PER_GIB,
        'interface' => null,
    ]);

    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}", [
            'size' => 30 * BYTES_PER_GIB,
        ])
        ->assertOk();

    expect((int) $disk->refresh()->size)->toBe(30 * BYTES_PER_GIB);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/resize'));
});

it('rejects shrinking a disk', function () {
    fakeProxmoxWithStorage();
    [, , , $server] = createServerModel();
    $disk = ServerDisk::factory()->for($server)->secondary()->create([
        'size' => 20 * BYTES_PER_GIB,
        'interface' => 'scsi1',
    ]);

    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}", [
            'size' => 10 * BYTES_PER_GIB,
        ])
        ->assertStatus(400)
        ->assertJsonPath('code', 'cannot_shrink_disk');
});

it('rejects resizing the primary disk', function () {
    fakeProxmoxWithStorage();
    [, , , $server] = createServerModel();
    $disk = ServerDisk::factory()->for($server)->create([
        'is_primary' => true,
        'size' => 20 * BYTES_PER_GIB,
        'interface' => 'scsi0',
    ]);

    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}", [
            'size' => 40 * BYTES_PER_GIB,
        ])
        ->assertStatus(400)
        ->assertJsonPath('code', 'cannot_modify_primary_disk');
});

it('removes a secondary disk, detaching and purging its volume', function () {
    // Model the two-step PVE removal: `delete=scsiN` only detaches (the volume
    // reappears as `unused0`), which a second `delete=unused0` then destroys.
    // A closure fake (not a sequence) so it's robust to request count/order.
    $detached = false;
    Http::fake([
        '*/config' => function ($request) use (&$detached) {
            if ($request->method() === 'POST') {
                if (($request['delete'] ?? null) === 'scsi1') {
                    $detached = true;
                }

                return Http::response(['data' => 'ok'], 200);
            }

            // Before detach the disk is on scsi1; after, it lingers as unused0.
            return Http::response(serverConfigFixture(
                $detached ? ['unused0' => 'nvme:vm-1-disk-1'] : ['scsi1' => 'nvme:vm-1-disk-1,size=10G'],
            ), 200);
        },
        '*' => Http::response(['data' => 'dummy-upid'], 200),
    ]);
    [, , , $server] = createServerModel();
    $storage = Storage::factory()->create(['name' => 'nvme']);
    $disk = ServerDisk::factory()->for($server)->for($storage)->secondary()->create([
        'size' => 10 * BYTES_PER_GIB,
        'interface' => 'scsi1',
    ]);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}")
        ->assertNoContent();

    // Detached the interface, then destroyed the freed unused0 volume.
    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && ($request['delete'] ?? null) === 'scsi1');
    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && ($request['delete'] ?? null) === 'unused0');
    expect(ServerDisk::find($disk->id))->toBeNull();
});

it('removes an unbuilt secondary disk without touching Proxmox', function () {
    fakeProxmox();
    [, , , $server] = createServerModel();
    $disk = ServerDisk::factory()->for($server)->secondary()->create([
        'interface' => null,
    ]);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}")
        ->assertNoContent();

    expect(ServerDisk::find($disk->id))->toBeNull();
    Http::assertNothingSent();
});

it('rejects removing the primary disk', function () {
    fakeProxmox();
    [, , , $server] = createServerModel();
    $disk = ServerDisk::factory()->for($server)->create([
        'is_primary' => true,
        'interface' => 'scsi0',
    ]);

    $this->actingAs(admin())
        ->deleteJson("/api/admin/servers/{$server->uuid}/disks/{$disk->id}")
        ->assertStatus(400)
        ->assertJsonPath('code', 'cannot_modify_primary_disk');

    expect(ServerDisk::find($disk->id))->not->toBeNull();
});

it('scopes the disk to its server (cross-server is a 404)', function () {
    fakeProxmoxWithStorage();
    [, , , $serverA] = createServerModel();
    [, , , $serverB] = createServerModel();
    $diskB = ServerDisk::factory()->for($serverB)->secondary()->create([
        'size' => 10 * BYTES_PER_GIB,
        'interface' => 'scsi1',
    ]);

    $this->actingAs(admin())
        ->patchJson("/api/admin/servers/{$serverA->uuid}/disks/{$diskB->id}", [
            'size' => 20 * BYTES_PER_GIB,
        ])
        ->assertNotFound();
});

it('forbids a non-admin from managing disks', function () {
    [$owner, , , $server] = createServerModel();
    $storage = Storage::factory()->create();

    $this->actingAs($owner)
        ->postJson("/api/admin/servers/{$server->uuid}/disks", [
            'storage_id' => $storage->id,
            'size' => 10 * BYTES_PER_GIB,
        ])
        ->assertForbidden();
});
