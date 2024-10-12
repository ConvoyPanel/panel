<?php

namespace Convoy\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Data;

class AddressConfigData extends Data
{
    public function __construct(
        public ?AddressData $ipv4,
        public ?AddressData $ipv6,
    ) {
    }
}
