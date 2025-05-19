<?php

namespace App\Data\Server\Proxmox\Network;

use Spatie\LaravelData\Data;

class IpsetData extends Data
{
    public function __construct(
        public string $name,
        public ?string $comment,
    ) {}
}
