<?php

namespace Convoy\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Data;

class DiskData extends Data
{
    public function __construct(
        public string  $name,
        public int     $size,
        public ?string $display_name,
    )
    {
    }
}
