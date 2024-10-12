<?php

namespace App\Data\Server\Proxmox\Console;

use Spatie\LaravelData\Data;

class NoVncCredentialsData extends Data
{
    public function __construct(
        public int    $port,
        public string $ticket,
        public string $pve_auth_cookie,
    ) {
    }
}
