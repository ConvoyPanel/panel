<?php

namespace Convoy\Models\Objects\Server\Allocations\Network;

use Spatie\LaravelData\Data;

class AddressObject extends Data
{
    public function __construct(
        public string $address,
        public int $cidr,
        public string $gateway,
        public string|null $mac_address,
    ) {
    }
}
