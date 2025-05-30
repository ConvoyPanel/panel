<?php

namespace App\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Data;

class VgaConfigData extends Data
{
    public function __construct(
        public string $type,
        public ?int $memory,
        public ?string $clipboardType,
    ) {}
}
