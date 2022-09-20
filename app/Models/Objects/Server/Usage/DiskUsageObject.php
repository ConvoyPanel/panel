<?php

namespace Convoy\Models\Objects\Server\Usage;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class DiskUsageObject extends Data
{
    public function __construct(
        public int $write,
        public int $read,
    ) {
    }
}