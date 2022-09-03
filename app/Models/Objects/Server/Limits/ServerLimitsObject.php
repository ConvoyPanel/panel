<?php

namespace App\Models\Objects\Server\Limits;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerLimitsObject extends Data
{
    public function __construct(
        public int $cpu,
        public int $memory,
        public array|Optional $address_ids,
        public int|Optional $disk,
        public AddressLimitsObject|Optional $addresses,
    ) {
    }
}