<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/**
 * How much of a finite, byte-denominated resource (memory, storage) is committed
 * versus the total available, with the derived percentage.
 */
class ResourceAllocationData extends Data
{
    public function __construct(
        public int $allocated,
        public int $total,
        public float $percent,
    ) {}
}
