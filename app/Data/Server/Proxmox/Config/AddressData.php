<?php

namespace App\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public string $address,
        public int    $cidr,
        public string $gateway,
    ) {
    }
}
