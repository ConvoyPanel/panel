<?php

namespace Convoy\Data\Server\Eloquent;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class ServerAddressesData extends Data
{
    public function __construct(
        #[DataCollectionOf(AddressData::class)]
        public DataCollection $ipv4,
        #[DataCollectionOf(AddressData::class)]
        public DataCollection $ipv6,
    ) {
    }
}
