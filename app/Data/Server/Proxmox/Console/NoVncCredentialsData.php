<?php

namespace Convoy\Data\Server\Proxmox\Console;

use Spatie\LaravelData\Data;

class NoVncCredentialsData extends Data
{
    public function __construct(
        public int    $vmid,
        public int    $port,
        public string $ticket,
    )
    {
    }
}