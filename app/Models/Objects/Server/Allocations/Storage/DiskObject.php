<?php

namespace App\Models\Objects\Server\Allocations\Storage;

use Spatie\LaravelData\Data;

class DiskObject extends Data
{
    public function __construct(
        public string $disk,
        public int $size,
    ) {
    }
}