<?php

namespace App\Data\Server\Eloquent;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IP;
use Spatie\LaravelData\Data;

class AddressData extends Data
{
    public function __construct(
        public int $id,
        public int $address_pool_id,
        public ?int $server_id,
        #[In(['ipv4', 'ipv6'])]
        public string $type,
        #[IP]
        public string $address,
        public int $cidr,
        public string $gateway,
        public ?string $mac_address,
    ) {
    }
}
