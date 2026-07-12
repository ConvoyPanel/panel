<?php

namespace App\Data\Node\Status;

use Spatie\LaravelData\Data;

class FilesystemUsageData extends Data
{
    public function __construct(
        public int $used,
        public int $free,
        public int $available,
        public int $total,
    ) {}
}
