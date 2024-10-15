<?php

namespace App\Data\Node\Storage;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

class IsoData extends Data
{
    public function __construct(
        public string $file_name,
        public int $size,
        public CarbonInterface $created_at,
    ) {
    }
}
