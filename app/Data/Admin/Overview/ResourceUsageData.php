<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/** Current use of a byte-denominated host resource. */
class ResourceUsageData extends Data
{
    public function __construct(
        public int $used,
        public int $total,
        public float $percent,
    ) {}
}
