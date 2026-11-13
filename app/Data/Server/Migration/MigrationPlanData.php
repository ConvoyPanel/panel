<?php

namespace App\Data\Server\Migration;

use Spatie\LaravelData\Data;

/**
 * Everything the migrate dialog needs to draw itself before anything is chosen.
 */
class MigrationPlanData extends Data
{
    public function __construct(
        public readonly int $sourceNodeId,
        public readonly string $sourceNodeName,
        public readonly bool $isRunning,
        /**
         * Devices passed through to the guest that pin it to this node, as PVE
         * names them. Non-empty means every candidate is blocked, and saying so
         * once above the list beats repeating it on every row.
         *
         * @var array<int, string>
         */
        public readonly array $localResources,
        /** @var array<int, MigrationCandidateData> */
        public readonly array $candidates,
        /**
         * Why there are no candidates at all, when there are none. A standalone
         * node has no cluster to move within; a single-member cluster has
         * nowhere to go.
         */
        public readonly ?string $emptyReason,
    ) {}
}
