<?php

namespace App\Data\Server;

use Spatie\LaravelData\Data;

class ServerTerminalData extends Data
{
    public function __construct(
        public string $ticket,
        public string $node,
        public int $vmid,
        public string $fqdn,
        public int $port,
    ) {}
}
