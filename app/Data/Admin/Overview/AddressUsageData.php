<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

class AddressUsageData extends Data
{
    public function __construct(
        public int $pools,
        public int $total,
        public int $assigned,
        public int $available,
        public float $percent,
    ) {}
}
