<?php

use App\Services\Images\OsProfiles;

it('gives Windows an OVMF default and everything else SeaBIOS', function () {
    expect(OsProfiles::defaults('win11')['bios'])->toBe('ovmf')
        ->and(OsProfiles::defaults('w2k8')['bios'])->toBe('ovmf')
        ->and(OsProfiles::defaults('l26')['bios'])->toBe('seabios');
});

it('never pins the machine version', function () {
    // A published pin names the QEMU of whichever machine built the image and
    // hard-fails on a node running an older one.
    expect(OsProfiles::defaults('l26')['machine'])->toBe('q35');
});

it('lets an overlay win over the default', function () {
    $merged = OsProfiles::merge('l26', ['bios' => 'ovmf']);

    expect($merged['bios'])->toBe('ovmf')
        ->and($merged['scsihw'])->toBe('virtio-scsi-single');
});

it('treats an unset key as inherit rather than clear', function () {
    // This is what lets the admin form show every field while asking for none:
    // emptying one has to mean "go back to the default", not "set it to null".
    $merged = OsProfiles::merge('l26', ['bios' => null]);

    expect($merged['bios'])->toBe('seabios');
});

it('keeps the panel-owned slot keys out of what Proxmox is sent', function () {
    $keys = OsProfiles::proxmoxKeys(OsProfiles::merge('l26', []));

    expect($keys)->not->toHaveKey('boot_disk_slot')
        ->and($keys)->not->toHaveKey('cloudinit_slot')
        ->and($keys)->toHaveKey('scsihw');
});
