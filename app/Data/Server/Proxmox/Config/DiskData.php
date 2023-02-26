<?php

namespace Convoy\Data\Server\Proxmox\Config;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class DiskData extends Data
{
//    public function __construct(
//        #[In(['disk', 'media'])]
//        public string $type,
//        public string  $name,
//        public int     $size,
//        public ?string $display_name,
//    )
//    {
//    }

    public function __construct(
        public string $interface,
        public bool $is_primary_disk,

        public bool $is_media,
        public ?string $media_name,

        public int $size,
    ){}
}
