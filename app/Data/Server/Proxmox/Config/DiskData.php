<?php

namespace Convoy\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class DiskData extends Data
{
    public function __construct(
        #[In(['disk', 'media'])]
        public string $type,
        public string  $name,
        public int     $size,
        public ?string $display_name,
    )
    {
    }
}
