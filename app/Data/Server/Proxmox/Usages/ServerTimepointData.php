<?php

namespace App\Data\Server\Proxmox\Usages;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ServerTimepointData extends Data
{
    public function __construct(
        public float $cpu_used,
        public float $memory_used,
        public ServerNetworkData $network,
        public ServerDiskData $disk,
        public CarbonImmutable $timestamp,
    ) {
    }
}
