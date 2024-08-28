<?php

namespace Convoy\Data\Server\Proxmox\Usages;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class ServerTimepointData extends Data
{
    public function __construct(
        public int               $cpu_used,
        public int               $memory_used,
        public ServerNetworkData $network,
        public ServerDiskData    $disk,
        public CarbonImmutable   $timestamp,
    )
    {
    }
}
