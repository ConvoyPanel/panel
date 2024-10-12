<?php

namespace App\Data\Helpers;

use Spatie\LaravelData\Data;
use App\Enums\Helpers\ChecksumAlgorithm;

class ChecksumData extends Data
{
    public function __construct(
        public string            $checksum,
        public ChecksumAlgorithm $algorithm,
    ) {
    }
}
