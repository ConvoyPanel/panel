<?php

namespace Convoy\Data\Server\Proxmox\Usages;

use Spatie\LaravelData\Data;

class ServerNetworkData extends Data
{
    public function __construct(
        public int $in,
        public int $out,
    ) {
    }
}
