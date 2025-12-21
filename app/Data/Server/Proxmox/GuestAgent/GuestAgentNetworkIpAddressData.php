<?php

namespace App\Data\Server\Proxmox\GuestAgent;

use Spatie\LaravelData\Data;
use Illuminate\Support\Arr;

class GuestAgentNetworkIpAddressData extends Data
{
    public function __construct(
        public string $type,
        public string $ipAddress,
        public int $prefix,
    ) {}

    public static function fromRaw(array $raw): self
    {
        return new self(
            type: Arr::get($raw, 'ip-address-type', ''),
            ipAddress: Arr::get($raw, 'ip-address', ''),
            prefix: (int) Arr::get($raw, 'prefix', 0),
        );
    }
}
