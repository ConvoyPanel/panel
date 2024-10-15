<?php

namespace App\Data\Server\Proxmox\Usages;

use Spatie\LaravelData\Data;

class ServerUsagesData extends Data
{
    public function __construct(
        public int $bandwidth,
        public ServerNetworkData $network,
        public ServerDiskData $disk,
    ) {
    }
}
