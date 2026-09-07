<?php

use App\Rules\ValidHardwareProfile;
use Illuminate\Support\Facades\Validator;

function hardwareErrors(array $hardware): array
{
    return Validator::make(
        ['hardware' => $hardware],
        ['hardware' => [new ValidHardwareProfile]],
    )->errors()->get('hardware');
}

it('accepts a profile Proxmox would accept', function () {
    expect(hardwareErrors(['bios' => 'ovmf', 'scsihw' => 'virtio-scsi-single']))->toBeEmpty();
});

it('rejects a value outside Proxmox\'s own enum', function () {
    // The enums come from PVE's schema rather than a list maintained here, so
    // this is the node's opinion and not the panel's guess.
    expect(hardwareErrors(['bios' => 'coreboot']))
        ->toHaveCount(1)
        ->and(hardwareErrors(['bios' => 'coreboot'])[0])->toContain('seabios');
});

it('rejects a setting Proxmox does not have', function () {
    expect(hardwareErrors(['turbo' => 'yes'])[0])->toContain('does not accept');
});

it('refuses keys the panel writes itself', function () {
    // Letting an overlay set these would mean it silently overwriting the disk
    // the image is being imported into, or the identity of the guest.
    foreach (['vmid', 'scsi0', 'efidisk0', 'net0', 'cores', 'sshkeys', 'ostype'] as $key) {
        expect(hardwareErrors([$key => 'anything']))
            ->toHaveCount(1, "expected `{$key}` to be refused")
            ->and(hardwareErrors([$key => 'anything'])[0])->toContain('sets `'.$key.'` itself');
    }
});

it('accepts the slot keys it owns, and only as slots', function () {
    expect(hardwareErrors(['cloudinit_slot' => 'ide2']))->toBeEmpty()
        ->and(hardwareErrors(['boot_disk_slot' => 'scsi0']))->toBeEmpty()
        ->and(hardwareErrors(['cloudinit_slot' => 'local:cloudinit'])[0])->toContain('disk slot');
});

it('takes Proxmox booleans in the shapes Proxmox uses', function () {
    expect(hardwareErrors(['numa' => 1]))->toBeEmpty()
        ->and(hardwareErrors(['numa' => '0']))->toBeEmpty()
        ->and(hardwareErrors(['numa' => true]))->toBeEmpty()
        ->and(hardwareErrors(['numa' => 'maybe']))->toHaveCount(1);
});

it('rejects a list where a single value belongs', function () {
    expect(hardwareErrors(['machine' => ['q35', 'pc']])[0])->toContain('single value');
});
