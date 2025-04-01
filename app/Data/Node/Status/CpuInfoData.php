<?php

namespace App\Data\Node\Status;

use Spatie\LaravelData\Data;

class CpuInfoData extends Data
{
    public function __construct(
        public int $socketCount,
        public int $coreCount,
        public int $cpuCount,
        public string $model,
        public string $flags,
    ) {

    }
}