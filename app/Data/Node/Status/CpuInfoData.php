<?php

namespace App\Data\Node\Status;

use Spatie\LaravelData\Data;

class CpuInfoData extends Data
{
    public function __construct(
        public int $cpuCount,
        public int $socketCount,
        public int $coreCount,
        public string $model,
        public string $flags,
    ) {

    }
}