<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class GuestAgentNetworkInterfaceData extends Data
{
    public function __construct(
        public string $name,
        public ?string $hardwareAddress,
        /** @var Collection<int, GuestAgentNetworkIpAddressData> */
        public Collection $ipAddresses,
    ) {}

    public static function fromRaw(array $raw): self
    {
        $addresses = Arr::get($raw, 'ip-addresses', []);

        return new self(
            name: Arr::get($raw, 'name', ''),
            hardwareAddress: Arr::get($raw, 'hardware-address'),
            ipAddresses: GuestAgentNetworkIpAddressData::collect($addresses, Collection::class),
        );
    }
}
