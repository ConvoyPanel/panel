<?php

use App\Data\Server\Proxmox\Config\TpmStateDiskData;

it('parses a fully specified tpmstate string', function () {
    $disk = TpmStateDiskData::fromRaw('local-lvm:vm-100-disk-2,size=4M,version=v2.0');

    expect($disk->volume)->toBe('local-lvm:vm-100-disk-2')
        ->and($disk->version)->toBe('v2.0')
        ->and($disk->size)->toBe(4)
        ->and($disk->extraProperties)->toBe([]);
});

it('parses a bare volume with no options', function () {
    $disk = TpmStateDiskData::fromRaw('local-lvm:vm-100-disk-2');

    expect($disk->volume)->toBe('local-lvm:vm-100-disk-2')
        ->and($disk->version)->toBe('')
        ->and($disk->size)->toBe(0);
});

it('accepts a file= or volume= keyed head', function () {
    expect(TpmStateDiskData::fromRaw('file=local-lvm:vm-1-disk-0,version=v2.0')->volume)
        ->toBe('local-lvm:vm-1-disk-0')
        ->and(TpmStateDiskData::fromRaw('volume=local-lvm:vm-1-disk-0,version=v2.0')->volume)
        ->toBe('local-lvm:vm-1-disk-0');
});

it('round-trips the modeled keys back to the PVE format', function () {
    $disk = TpmStateDiskData::fromRaw('local-lvm:vm-100-disk-2,size=4M,version=v2.0');

    expect($disk->toProxmoxString())
        ->toContain('file=local-lvm:vm-100-disk-2')
        ->toContain('version=v2.0')
        ->toContain('size=4');
});

it('preserves sub-keys it does not model through a round-trip', function () {
    $disk = TpmStateDiskData::fromRaw('local-lvm:vm-100-disk-2,version=v2.0,some_future_option=xyz');

    expect($disk->extraProperties)->toBe(['some_future_option' => 'xyz']);
    expect($disk->toProxmoxString())->toContain('some_future_option=xyz');
});
