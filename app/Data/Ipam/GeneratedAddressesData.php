<?php

namespace App\Data\Ipam;

use Spatie\LaravelData\Data;

class GeneratedAddressesData extends Data
{
    public function __construct(
        public int $createdCount,
        public int $remaining,
        public bool $isComplete,
        // Sparse blocks (large v4 / any v6) are never pre-materialized — the allocator mints their
        // addresses on demand — so there is nothing to enumerate here.
        public bool $sparse = false,
    ) {}
}
