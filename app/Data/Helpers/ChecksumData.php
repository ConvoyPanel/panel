<?php

namespace App\Data\Helpers;

use App\Enums\Helpers\ChecksumAlgorithm;
use Spatie\LaravelData\Data;

class ChecksumData extends Data
{
    public function __construct(
        public string $checksum,
        public ChecksumAlgorithm $algorithm,
    ) {
    }
}
