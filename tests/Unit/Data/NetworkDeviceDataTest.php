<?php

use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Enums\Server\NetworkDeviceModel;

it('parses every modeled tail field into a typed value', function () {
    $raw = ['net3' => 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr1,tag=42,firewall=1,rate=10,queues=4,mtu=9000,link_down=1,trunks=10;20'];

    $device = NetworkDeviceData::fromRaw($raw)->first();

    expect($device->id)->toBe(3)
        ->and($device->model)->toBe(NetworkDeviceModel::VIRTIO)
        ->and($device->macAddress)->toBe('AA:BB:CC:DD:EE:FF')
        ->and($device->bridge)->toBe('vmbr1')
        ->and($device->vlanTag)->toBe(42)
        ->and($device->isFirewallEnabled)->toBeTrue()
        // Proxmox rate is decimal MB/s: rate=10 -> 10_000_000 bytes/s (not binary MiB).
        ->and($device->rateLimit)->toBe(10 * 1_000_000)
        ->and($device->packetQueueCount)->toBe(4)
        ->and($device->mtu)->toBe(9000)
        ->and($device->isLinkDown)->toBeTrue()
        ->and($device->vlanTrunks)->toBe('10;20')
        ->and($device->extraProperties)->toBe([]);
});

it('round-trips every modeled tail field back to the PVE format', function () {
    $raw = 'virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr1,tag=42,firewall=1,rate=10,queues=4,mtu=9000,link_down=1,trunks=10;20';

    $device = NetworkDeviceData::fromRaw(['net0' => $raw])->first();
    [, $value] = $device->toProxmoxString();

    // Order-independent: PVE parses the tail as an unordered bag.
    foreach (explode(',', $raw) as $segment) {
        expect($value)->toContain($segment);
    }
    // And nothing extra was invented.
    expect(explode(',', $value))->toHaveCount(count(explode(',', $raw)));
});

it('emits pve booleans as 1/0 and omits unset fields', function () {
    $device = NetworkDeviceData::fromRaw(['net0' => 'virtio=AA:BB:CC:DD:EE:FF,firewall=0'])->first();

    expect($device->isFirewallEnabled)->toBeFalse();

    [, $value] = $device->toProxmoxString();

    expect($value)->toBe('virtio=AA:BB:CC:DD:EE:FF,firewall=0');
});

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
