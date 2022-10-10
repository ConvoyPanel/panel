<?php

namespace Convoy\Models\Objects\Server\Usage;

use Spatie\LaravelData\Data;

class DiskUsageObject extends Data
{
    public function __construct(
        public int $write,
        public int $read,
    ) {
    }
}
