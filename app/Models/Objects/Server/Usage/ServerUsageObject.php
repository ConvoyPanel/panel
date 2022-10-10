<?php

namespace Convoy\Models\Objects\Server\Usage;

use Spatie\LaravelData\Data;

class ServerUsageObject extends Data
{
    public function __construct(
        public int $uptime,
        public NetworkUsageObject $network,
        public DiskUsageObject $disk,
    ) {
    }
}
