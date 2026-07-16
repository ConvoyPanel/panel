<?php

namespace App\Data\Server\Proxmox\Usages;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ServerTimepointData extends Data
{
    public function __construct(
        public float $cpuUsed,
        public float $memoryUsed,
        public ServerNetworkData $network,
        public ServerDiskData $disk,
        public CarbonImmutable $timestamp,
    ) {}
}
