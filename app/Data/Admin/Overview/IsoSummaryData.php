<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

class IsoSummaryData extends Data
{
    public function __construct(
        public int $total,
        public int $successful,
        public int $pending,
    ) {}
}
