<?php

namespace App\Models\Objects\Server\Usage;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ServerUsageObject extends Data
{
    public function __construct(
        public int $uptime,
        public NetworkUsageObject $network,
        public DiskUsageObject $disk,
    ) {
    }
}