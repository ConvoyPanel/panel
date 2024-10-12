<?php

namespace Convoy\Data\Server\Deployments;

use Convoy\Data\Server\Eloquent\AddressData;
use Spatie\LaravelData\Data;

class CloudinitAddressConfigData extends Data
{
    public function __construct(
        public ?AddressData $ipv4,
        public ?AddressData $ipv6,
    ) {
    }
}
