<?php

namespace App\Data\Node\Status;

use App\Enums\Node\BootMode;
use Spatie\LaravelData\Data;

class BootInfoData extends Data
{
    public function __construct(
        public BootMode $mode,
        public ?bool $secureBoot,
    ) {}
}
