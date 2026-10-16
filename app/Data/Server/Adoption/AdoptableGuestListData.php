<?php

namespace App\Data\Server\Adoption;

use Spatie\LaravelData\Data;

class AdoptableGuestListData extends Data
{
    public function __construct(
        /** @var array<int, AdoptableGuestData> */
        public readonly array $guests,
        /**
         * Nodes that could not be asked, with the reason. A cluster that is
         * down has to say so: an empty list and an unreachable list look
         * identical to a reader, and one of them means "nothing to adopt"
         * while the other means "we do not know".
         *
         * @var array<int, UnreachableScopeData>
         */
        public readonly array $unreachable,
    ) {}
}
