<?php

namespace Convoy\Models\Objects\Server\Configuration;

use Spatie\LaravelData\Data;

class ServerLimitsObject extends Data
{
    public function __construct(
        public int $cpu,
        public int $memory,
        public int $disk,
    ) {
    }
}