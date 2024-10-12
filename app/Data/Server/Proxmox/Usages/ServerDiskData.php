<?php

namespace Convoy\Data\Server\Proxmox\Usages;

use Spatie\LaravelData\Data;

class ServerDiskData extends Data
{
    public function __construct(
        public int $write,
        public int $read,
    ) {
    }
}
