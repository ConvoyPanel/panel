<?php

namespace App\Data\Server\Adoption;

use Spatie\LaravelData\Data;

/** A node Convoy asked for its guest list and did not get one from. */
class UnreachableScopeData extends Data
{
    public function __construct(
        public readonly int $nodeId,
        public readonly string $nodeName,
        public readonly string $reason,
    ) {}
}
