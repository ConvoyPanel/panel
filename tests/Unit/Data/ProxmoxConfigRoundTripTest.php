<?php

use App\Data\Server\Proxmox\Config\NetworkDeviceData;
use App\Data\Server\Proxmox\Config\TpmStateDiskData;
use App\Extensions\Spatie\Data\Proxmox\PropertyList;

/**
 * Golden-master round-trip safety net for the property-list codec DTOs that
 * emit (NetworkDeviceData, TpmStateDiskData): parsing a real PVE string,
 * re-emitting it, and parsing again must not silently drop fields.
 */
it('round-trips real PVE net device strings without dropping fields', function (string $raw) {
    $emit = fn (string $line) => NetworkDeviceData::fromRaw(['net0' => $line])->first()->toProxmoxString()[1];

    $e1 = $emit($raw);
    $e2 = $emit($e1);

    // Re-emission is idempotent — the round-trip drops nothing.
    expect($e2)->toBe($e1);

    // The whole tail survives the first cycle intact (net has no lossy
    // normalization; only the positional model=mac head is special-cased).
    [$rawHead, $rawPairs] = PropertyList::explode($raw);
    [$e1Head, $e1Pairs] = PropertyList::explode($e1);

    expect($e1Head)->toBe($rawHead)
        ->and($e1Pairs)->toEqual($rawPairs);
})->with([
    'minimal' => ['virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0'],
    'fully specified' => ['virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,firewall=1,tag=100,queues=4,mtu=9000,link_down=1,rate=10'],
    'disabled firewall with vlan trunks' => ['e1000=DE:AD:BE:EF:00:01,bridge=vmbr1,firewall=0,trunks=10;20;30'],
    'unmodeled keys preserved' => ['virtio=AA:BB:CC:DD:EE:FF,bridge=vmbr0,mtu=1500,mystery=42,another_unknown=foo'],
]);

it('round-trips real PVE tpmstate strings, preserving version and unmodeled keys', function (string $raw) {
    $e1 = TpmStateDiskData::fromRaw($raw)->toProxmoxString();
    $e2 = TpmStateDiskData::fromRaw($e1)->toProxmoxString();

    // Re-emission is idempotent.
    expect($e2)->toBe($e1);

    // Every tail key from the original survives, except `size`, whose unit
    // suffix (4M -> 4) is intentionally normalized (documented, informational
    // for tpmstate).
    [, $rawPairs] = PropertyList::explode($raw);
    [, $e1Pairs] = PropertyList::explode($e1);

    foreach ($rawPairs as $key => $value) {
        if ($key === 'size') {
            continue;
        }

        expect($e1Pairs)->toHaveKey($key, $value);
    }
})->with([
    'sized and versioned' => ['local-lvm:vm-100-disk-2,size=4M,version=v2.0'],
    'unmodeled key preserved' => ['local-lvm:vm-1-disk-0,version=v2.0,future_opt=xyz'],
]);
