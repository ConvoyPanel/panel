<?php

namespace App\Data\Server;

use Spatie\LaravelData\Data;

class MediaData extends Data
{
    public function __construct(
        public string $uuid,
        public string $name,
        public int $size,
        public bool $hidden,
        public bool $mounted,
    ) {}
}
