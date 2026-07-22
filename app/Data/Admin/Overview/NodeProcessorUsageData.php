<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

class NodeProcessorUsageData extends Data
{
    public function __construct(
        public int $count,
        public float $percent,
    ) {}
}
