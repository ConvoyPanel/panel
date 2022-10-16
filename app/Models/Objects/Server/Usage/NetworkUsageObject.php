<?php

namespace Convoy\Models\Objects\Server\Usage;

use Spatie\LaravelData\Data;

class NetworkUsageObject extends Data
{
    public function __construct(
        public int $in,
        public int $out,
        public int $monthly_total,
    ) {
    }
}
