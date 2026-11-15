<?php

use Convoy\Models\ISO;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function vmConfig(array $drives): array
{
    return ['data' => [
        'scsi0' => 'local-lvm:vm-100-disk-0,size=20G',
        'boot' => 'order=scsi0',
        ...$drives,
    ]];
}

function configUpdates(): array
{
    return Http::recorded(
        fn (Request $request) => $request->method() === 'POST' && str_ends_with($request->url(), '/config'),
    )->map(fn ($pair) => $pair[0]->data())->values()->all();
}

beforeEach(function () {
    [$this->owner, $_, $this->node, $this->server] = createServerModel();
    $this->admin = User::factory()->create(['root_admin' => true]);
    $this->iso = ISO::factory()->for($this->node)->create([
        'name' => 'netboot.xyz',
        'file_name' => 'netboot.xyz.iso',
        'is_successful' => true,
        'completed_at' => now(),
    ]);
});

it('ejects an ISO from the servers it is mounted on before deleting it', function () {
    $other = Server::factory()->create([
        'node_id' => $this->node->id,
        'user_id' => $this->owner->id,
        'vmid' => $this->server->vmid + 1,
    ]);

    Http::fake([
        "*/qemu/{$this->server->vmid}/config" => Http::response(vmConfig([
            'ide1' => 'local:iso/netboot.xyz.iso,media=cdrom',
            'ide2' => 'local:iso/other.iso,media=cdrom',
        ])),
        "*/qemu/{$other->vmid}/config" => Http::response(vmConfig([])),
        '*/storage/*/content/*' => Http::response(['data' => null]),
    ]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/nodes/{$this->node->id}/isos/{$this->iso->uuid}")
        ->assertNoContent();

    expect(configUpdates())->toBe([['delete' => 'ide1']])
        ->and(ISO::find($this->iso->id))->toBeNull();
    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
});

it('deletes an ISO when a server on the node has no VM', function () {
    Http::fake([
        '*/config' => Http::response([
            'message' => "Configuration file 'nodes/proxmox/qemu-server/{$this->server->vmid}.conf' does not exist",
        ], 500),
        '*/storage/*/content/*' => Http::response(['data' => null]),
    ]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/nodes/{$this->node->id}/isos/{$this->iso->uuid}")
        ->assertNoContent();

    expect(ISO::find($this->iso->id))->toBeNull();
});

it('keeps an ISO when the node fails before it could be ejected', function () {
    Http::fake([
        '*/config' => Http::response(['message' => 'proxy loop detected'], 500),
        '*/storage/*/content/*' => Http::response(['data' => null]),
    ]);

    $this->actingAs($this->admin)
        ->deleteJson("/api/admin/nodes/{$this->node->id}/isos/{$this->iso->uuid}")
        ->assertStatus(502);

    expect(ISO::find($this->iso->id))->not->toBeNull();
    Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE');
});

it('lists a mounted ISO that was deleted from the library', function () {
    $this->iso->delete();

    Http::fake([
        '*/config' => Http::response(vmConfig(['ide1' => 'local:iso/netboot.xyz.iso,media=cdrom'])),
    ]);

    $this->actingAs($this->owner)
        ->getJson("/api/client/servers/{$this->server->uuid}/settings/hardware/isos")
        ->assertOk()
        ->assertJsonPath('data.0.name', 'netboot.xyz.iso')
        ->assertJsonPath('data.0.missing', true)
        ->assertJsonPath('data.0.mounted', true)
        ->assertJsonPath('data.0.interface', 'ide1');
});

it('unmounts a mounted ISO that was deleted from the library', function () {
    $this->iso->delete();

    Http::fake([
        '*/config' => Http::sequence()
            ->push(vmConfig(['ide1' => 'local:iso/netboot.xyz.iso,media=cdrom']))
            ->push(['data' => null]),
    ]);

    $this->actingAs($this->owner)
        ->postJson("/api/client/servers/{$this->server->uuid}/settings/hardware/drives/ide1/unmount")
        ->assertNoContent();

    expect(configUpdates())->toBe([['delete' => 'ide1']]);
});

it("won't unmount a drive that isn't a deleted ISO", function (string $interface) {
    Http::fake([
        '*/config' => Http::response(vmConfig([
            'ide0' => 'local-lvm:vm-100-cloudinit,media=cdrom',
            'ide1' => 'local:iso/netboot.xyz.iso,media=cdrom',
        ])),
    ]);

    $this->actingAs($this->owner)
        ->postJson("/api/client/servers/{$this->server->uuid}/settings/hardware/drives/{$interface}/unmount")
        ->assertStatus(400);

    expect(configUpdates())->toBe([]);
})->with(['scsi0', 'ide0', 'ide1', 'ide3']);
