<?php

namespace App\Data\Node\Status;

use Spatie\LaravelData\Data;

class KernelInfoData extends Data
{
    public function __construct(
        public string $build,
        public string $release,
        public string $os,
        public string $architecture,
    ) {}
}
