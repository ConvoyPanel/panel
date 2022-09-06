<?php

namespace App\Models\Objects\Server\Limits;

use App\Models\Objects\Server\Allocations\Network\AddressObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class AddressLimitsObject extends Data
{
    public function __construct(
        public AddressObject|null $ipv4,
        public AddressObject|null $ipv6,
    ) {
    }
}