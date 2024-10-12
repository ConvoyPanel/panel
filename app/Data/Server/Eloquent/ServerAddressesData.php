<?php

namespace App\Data\Server\Eloquent;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Attributes\DataCollectionOf;

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
