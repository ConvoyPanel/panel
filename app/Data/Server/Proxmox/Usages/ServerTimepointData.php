<?php

namespace Convoy\Data\Server\Proxmox\Usages;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ServerTimepointData extends Data
{
    public function __construct(
        public int               $cpuUsed,
        public int               $memoryUsed,
        public ServerNetworkData $network,
        public ServerDiskData    $disk,
        public CarbonImmutable   $timestamp,
    )
    {
    }
}
