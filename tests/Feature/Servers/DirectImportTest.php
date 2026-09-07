<?php

use App\Enums\Image\ImageDiskRole;
use App\Models\ImageDefinition;
use App\Models\ImageGroup;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * The create call is where a stored profile becomes a real machine.
 *
 * A clone inherited every hardware setting from the template it copied, so the
 * panel never had to be right about any of them. There is nothing to inherit
 * here: whatever this payload omits, the guest does without -- which is why the
 * assertions below are about the exact arguments, not about the call
 * succeeding.
 */
function makeImageVersion(array $hardware = [], string $ostype = 'l26', bool $withVarstore = false)
{
    $group = ImageGroup::create(['name' => 'Ubuntu']);

    $definition = ImageDefinition::create([
        'image_group_id' => $group->id,
        'name' => 'Ubuntu 24.04',
        'ostype' => $ostype,
        'hardware' => $hardware,
    ]);

    $disks = [[
        'slot' => 'scsi0',
        'role' => ImageDiskRole::SYSTEM->value,
        'url' => 'https://example.invalid/disk.qcow2',
        'path' => null,
        'sha256' => str_repeat('a', 64),
        'size' => 600 * 1024 * 1024,
        'virtual_size' => 8 * 1024 ** 3,
        'format' => 'qcow2',
    ]];

    if ($withVarstore) {
        $disks[] = [
            'slot' => 'efidisk0',
            'role' => ImageDiskRole::EFIVARS->value,
            'url' => 'https://example.invalid/vars.qcow2',
            'path' => null,
            'sha256' => str_repeat('b', 64),
            'size' => 528 * 1024,
            'virtual_size' => 528 * 1024,
            'format' => 'qcow2',
        ];
    }

    return $definition->versions()->create(['version' => '1.0.0', 'disks' => $disks]);
}

/** Capture the body of the guest-create request. */
function capturedCreatePayload(callable $build): array
{
    $captured = [];

    Http::fake(['*/api2/json/nodes/*/qemu' => function ($request) use (&$captured) {
        $captured = $request->data();

        return Http::response(['data' => 'UPID:create']);
    }]);

    $build();

    return $captured;
}

it('assembles the guest from the stored profile instead of cloning', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion();

    $payload = capturedCreatePayload(fn () => app(ProxmoxServerClient::class)
        ->setServer($server)
        ->create($version, [ImageDiskRole::SYSTEM->value => 'local:import/image.qcow2']));

    expect($payload['vmid'])->toBe($server->vmid)
        ->and($payload['ostype'])->toBe('l26')
        // Straight out of the OS default: nobody typed these, and a guest whose
        // scsihw is wrong does not find its root disk.
        ->and($payload['scsihw'])->toBe('virtio-scsi-single')
        ->and($payload['machine'])->toBe('q35')
        ->and($payload['bios'])->toBe('seabios');
});

it('imports the system disk at the source size and lets the resize grow it', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion();

    $payload = capturedCreatePayload(fn () => app(ProxmoxServerClient::class)
        ->setServer($server)
        ->create($version, [ImageDiskRole::SYSTEM->value => 'local:import/image.qcow2']));

    // Size 0 means "take the source's size". Naming a size here would be a
    // request to shrink whenever the plan is smaller, which Proxmox refuses.
    expect($payload['scsi0'])->toBe("{$server->storage->name}:0,import-from=local:import/image.qcow2")
        ->and($payload['boot'])->toBe('order=scsi0');
});

it('ships the varstore verbatim for an OVMF image', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion(ostype: 'win11', withVarstore: true);

    $payload = capturedCreatePayload(fn () => app(ProxmoxServerClient::class)
        ->setServer($server)
        ->create($version, [
            ImageDiskRole::SYSTEM->value => 'local:import/disk.qcow2',
            ImageDiskRole::EFIVARS->value => 'local:import/vars.qcow2',
        ]));

    // Imported, never regenerated: the varstore holds the boot entry and the
    // enrolled Secure Boot keys, and a fresh one leaves Windows unbootable.
    expect($payload['efidisk0'])->toContain('import-from=local:import/vars.qcow2')
        ->and($payload['bios'])->toBe('ovmf');
});

it('omits the varstore entirely when the image has none', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion();

    $payload = capturedCreatePayload(fn () => app(ProxmoxServerClient::class)
        ->setServer($server)
        ->create($version, [ImageDiskRole::SYSTEM->value => 'local:import/image.qcow2']));

    expect($payload)->not->toHaveKey('efidisk0');
});

it('gives the guest a cloud-init drive on the slot the profile names', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion(['cloudinit_slot' => 'ide2']);

    $payload = capturedCreatePayload(fn () => app(ProxmoxServerClient::class)
        ->setServer($server)
        ->create($version, [ImageDiskRole::SYSTEM->value => 'local:import/image.qcow2']));

    expect($payload['ide2'])->toBe("{$server->storage->name}:cloudinit")
        // The slot names are the panel's own bookkeeping and mean nothing to
        // Proxmox, so sending them would fail the call outright.
        ->and($payload)->not->toHaveKey('cloudinit_slot')
        ->and($payload)->not->toHaveKey('boot_disk_slot');
});

it('lets a definition override a default it disagrees with', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion(['scsihw' => 'virtio-scsi-pci', 'boot_disk_slot' => 'virtio0']);

    $payload = capturedCreatePayload(fn () => app(ProxmoxServerClient::class)
        ->setServer($server)
        ->create($version, [ImageDiskRole::SYSTEM->value => 'local:import/image.qcow2']));

    expect($payload['scsihw'])->toBe('virtio-scsi-pci')
        ->and($payload)->toHaveKey('virtio0')
        ->and($payload['boot'])->toBe('order=virtio0');
});

it('refuses to build a version with no system disk', function () {
    [, , , $server] = createServerModel();
    $version = makeImageVersion();

    Http::fake(['*' => Http::response(['data' => 'UPID:create'])]);

    expect(fn () => app(ProxmoxServerClient::class)->setServer($server)->create($version, []))
        ->toThrow(ConflictHttpException::class);
});
