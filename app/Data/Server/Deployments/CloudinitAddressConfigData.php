<?php

namespace Convoy\Data\Server\Deployments;

use Spatie\LaravelData\Data;
use Convoy\Data\Server\Eloquent\AddressData;

class CloudinitAddressConfigData extends Data
{
    public function __construct(
        public ?AddressData $ipv4,
        public ?AddressData $ipv6,
    )
    {
    }
}
