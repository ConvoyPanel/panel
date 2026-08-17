<?php

use App\Models\ISO;
use Illuminate\Support\Facades\Http;

it('can rename servers', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/rename",
        [
            'name' => 'advinservers is king',
            'hostname' => 'advinservers.com',
        ],
    );

    $response->assertCreated()
        ->assertJsonPath('data.name', 'advinservers is king')
        ->assertJsonPath('data.hostname', 'advinservers.com');
});

it('can change nameservers', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson(
        "/api/client/servers/{$server->uuid}/settings/network",
        [
            'nameservers' => ['1.1.1.1', '1.0.0.1'],
        ],
    );

    $response->assertOk();
});

it('can fetch sshkeys', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/auth",
    );

    $response->assertOk();
});

it('can change server passwords', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->putJson(
        "/api/client/servers/{$server->uuid}/settings/auth",
        [
            'type' => 'password',
            'password' => 'Advinservers is king!123',
        ],
    );

    $response->assertNoContent();
});

it('can fetch available ISOs', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    ISO::factory()->count(10)->create([
        'storage_id' => $server->storage_id,
        'hidden' => false,
    ]);

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos",
    );

    $response->assertOk();
});

it('can mount visible ISOs', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'storage_id' => $server->storage_id,
        'hidden' => false,
    ]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount",
    );

    $response->assertNoContent();
});

it('can\'t mount hidden ISOs as non-admin user', function () {
    [$user, $_, $_, $server] = createServerModel();

    $iso = ISO::factory()->create([
        'storage_id' => $server->storage_id,
        'hidden' => true,
    ]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$iso->uuid}/mount",
    );

    $response->assertStatus(403);
});

it('can\'t mount an ISO from a node the user\'s server is not on', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    // Default factory ISO lives on its own storage, which is NOT attached to
    // the server's node — so it is outside what getMedia would ever list.
    $foreignIso = ISO::factory()->create(['hidden' => false]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$foreignIso->uuid}/mount",
    );

    $response->assertStatus(403);
});

it('can\'t unmount an ISO from a node the user\'s server is not on', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $foreignIso = ISO::factory()->create(['hidden' => false]);

    $response = $this->actingAs($user)->postJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/isos/{$foreignIso->uuid}/unmount",
    );

    $response->assertStatus(403);
});

it('returns every device with the boot ordering over them', function () {
    // Nested DataCollections inherit the global `data` wrap, which would hand the
    // client {"data": [...]} where it expects a list to iterate over.
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/storage",
    );

    // The fixture is one sata disk plus the cloud-init drive, with only the
    // disk in the boot order -- so the devices list is strictly larger than the
    // ordering, which is the whole point of the shape.
    $response->assertOk()
        ->assertJsonPath('data.devices.0.interface', 'sata0')
        ->assertJsonPath('data.devices.0.media', 'disk')
        ->assertJsonPath('data.devices.1.interface', 'ide0')
        ->assertJsonPath('data.bootOrder', ['sata0']);

    expect($response->json('data.devices'))->toBeList()
        ->and($response->json('data.bootOrder'))->toBeList();
});

it('reads back every device in a multi-device boot order', function () {
    // PVE separates the devices inside `order=` with `;` and ends the property
    // with `,`. Reading only as far as the first `;` returned a one-device boot
    // order no matter what was saved, so switching a second device on and
    // saving it came straight back off on the next read.
    fakeProxmox([
        '*/config' => Http::response(serverConfigFixture(['boot' => 'order=ide0;sata0']), 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/storage",
    )
        ->assertOk()
        ->assertJsonPath('data.bootOrder', ['ide0', 'sata0']);
});

it('keeps the boot order when a legacy entry precedes it', function () {
    fakeProxmox([
        '*/config' => Http::response(serverConfigFixture(['boot' => 'legacy=dc,order=sata0;ide0']), 200),
    ]);

    [$user, $_, $_, $server] = createServerModel();

    $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/storage",
    )
        ->assertOk()
        ->assertJsonPath('data.bootOrder', ['sata0', 'ide0']);
});

it('narrows a device down to the fields the client can act on', function () {
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/storage",
    );

    // `sata0: local-lvm:vm-608782004-disk-0,discard=on,size=4712M,ssd=1`
    $response->assertOk()
        ->assertJsonPath('data.devices.0.volume', 'local-lvm:vm-608782004-disk-0')
        ->assertJsonPath('data.devices.0.isEmulatingSSD', true)
        ->assertJsonPath('data.devices.0.discardMode', 'on')
        ->assertJsonPath('data.devices.0.isCloudinitDrive', false)
        ->assertJsonPath('data.devices.0.mediaName', null);

    // PVE's host-identifying plumbing has no business on a client screen.
    expect($response->json('data.devices.0'))
        ->not->toHaveKeys(['wwn', 'serial', 'vendor', 'model']);
});

it('marks the cloud-init drive rather than calling it a CD-ROM', function () {
    // `ide0: local-lvm:vm-608782004-cloudinit,media=cdrom,size=4M` -- the 4 MiB
    // device that shows up unexplained on nearly every server.
    fakeProxmox();

    [$user, $_, $_, $server] = createServerModel();

    $response = $this->actingAs($user)->getJson(
        "/api/client/servers/{$server->uuid}/settings/hardware/storage",
    );

    $response->assertOk()
        ->assertJsonPath('data.devices.1.media', 'cdrom')
        ->assertJsonPath('data.devices.1.isCloudinitDrive', true)
        ->assertJsonPath('data.devices.1.mediaName', null);
});
