<?php

namespace Convoy\Data\Server\Eloquent;

use Spatie\LaravelData\Data;

class ServerUsagesData extends Data
{
    public function __construct(
        public int $bandwidth,
    ) {
    }
}
