<?php

namespace App\Models\Objects\Server\Limits;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerLimitsObject extends Data
{
    public function __construct(
        public int|null $cpu,
        public int|null $memory,
        public array|null $address_ids,
        public int|null $disk,
        public AddressLimitsObject|null $addresses,
    ) {
    }
}