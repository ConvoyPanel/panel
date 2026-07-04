<?php

use App\Data\Server\Proxmox\Config\NetworkDeviceData;

it('preserves sub-keys it does not model through a round-trip', function () {
    // `some_future_option` is not one of the keys we parse into typed fields.
    $raw = ['net0' => 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,firewall=1,some_future_option=xyz'];

    $device = NetworkDeviceData::fromRaw($raw)->first();

    expect($device->extraProperties)->toBe(['some_future_option' => 'xyz']);

    [$key, $value] = $device->toProxmoxString();

    expect($key)->toBe('net0');
    // The unknown key survives, alongside the fields we do model.
    expect($value)->toContain('some_future_option=xyz')
        ->toContain('bridge=vmbr0')
        ->toContain('firewall=1');
});

it('round-trips a device that has no extra keys without inventing any', function () {
    $device = NetworkDeviceData::fromRaw(['net0' => 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0'])->first();

    expect($device->extraProperties)->toBe([]);

    [, $value] = $device->toProxmoxString();

    expect($value)->toBe('virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0');
});
