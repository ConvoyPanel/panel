<?php

namespace App\Data\Server\Proxmox\Console;

use App\Enums\Node\Access\RealmType;
use Spatie\LaravelData\Data;

class XTermCredentialsData extends Data
{
    public function __construct(
        public int $port,
        public string $ticket,
        public string $username,
        public RealmType $realm_type,
        public string $pve_auth_cookie,
    ) {
    }
}
