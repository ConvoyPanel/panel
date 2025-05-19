<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Data;

class GeneratedAddressesData extends Data
{
    public function __construct(
        public int $createdCount,
        public int $remaining,
        public bool $isComplete,
    ) {}
}
