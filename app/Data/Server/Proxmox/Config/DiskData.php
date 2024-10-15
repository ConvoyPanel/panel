<?php

namespace App\Data\Server\Proxmox\Config;

use App\Enums\Server\DiskInterface;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class DiskData extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class)]
        public DiskInterface $interface,
        public bool $is_primary_disk,
        public bool $is_media,
        public ?string $media_name,
        public int $size,
    ) {
    }
}
