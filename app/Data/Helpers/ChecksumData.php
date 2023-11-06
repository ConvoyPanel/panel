<?php

namespace Convoy\Data\Helpers;

use Spatie\LaravelData\Data;
use Convoy\Enums\Helpers\ChecksumAlgorithm;

class ChecksumData extends Data
{
    public function __construct(
        public string            $checksum,
        public ChecksumAlgorithm $algorithm,
    )
    {
    }
}
