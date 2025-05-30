<?php

namespace App\Data\Server\Eloquent;

use App\Models\Address;
use Spatie\LaravelData\Data;

class PrimaryAddressesData extends Data
{
    public function __construct(
        public ?Address $ipv4,
        public ?Address $ipv6,
    ) {}
}
