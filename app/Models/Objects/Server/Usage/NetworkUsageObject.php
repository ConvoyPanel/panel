<?php

namespace Convoy\Models\Objects\Server\Usage;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class NetworkUsageObject extends Data
{
    public function __construct(
        public int $in,
        public int $out,
    ) {
    }
}