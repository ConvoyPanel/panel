<?php

namespace App\Data\Server\Proxmox\Network;

use IPLib\Range\RangeInterface;
use Spatie\LaravelData\Data;

class LockedIpData extends Data
{
    public function __construct(
        public RangeInterface $ip,
        public ?string $comment,
        public string $originalIp,
    ) {}
}
