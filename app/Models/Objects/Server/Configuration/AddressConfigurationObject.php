<?php

namespace App\Models\Objects\Server\Configuration;

use App\Models\Objects\Server\Allocations\Network\AddressObject;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class AddressConfigurationObject extends Data
{
    public function __construct(
        public AddressObject|null $ipv4,
        public AddressObject|null $ipv6,
    ) {
    }
}