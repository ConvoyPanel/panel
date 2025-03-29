<?php

namespace App\Data\Node\Status;

use Spatie\LaravelData\Data;

class MemoryUsageData extends Data
{
    public function __construct(
        public int $used,
        public int $free,
        public int $total,
    ) {}
}