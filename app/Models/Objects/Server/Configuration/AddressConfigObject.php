<?php

namespace Convoy\Models\Objects\Server\Configuration;

use Convoy\Models\Objects\Server\Allocations\Network\AddressObject;
use Spatie\LaravelData\Data;

class AddressConfigObject extends Data
{
    public function __construct(
        public AddressObject|null $ipv4,
        public AddressObject|null $ipv6,
    ) {
    }
}
