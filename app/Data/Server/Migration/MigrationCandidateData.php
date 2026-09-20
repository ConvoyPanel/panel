<?php

namespace App\Data\Server\Migration;

use App\Enums\Server\MigrationDisposition;
use Spatie\LaravelData\Data;

/**
 * One destination node, with the verdict on sending this server to it.
 */
class MigrationCandidateData extends Data
{
    public function __construct(
        public readonly int $nodeId,
        public readonly string $nodeName,
        public readonly string $locationName,
        public readonly MigrationDisposition $disposition,
        /** Why it is blocked, or what the operator has to do about it. Null unless blocked. */
        public readonly ?string $blockedReason,
        /** The bridge the server would land on. Null when the destination has no bridge of the right name. */
        public readonly ?string $bridgeName,
        /**
         * Whether PVE can move the guest without stopping it. False for a
         * stopped guest (nothing to keep running) and for every reallocation,
         * because a new address only reaches the guest through a reboot.
         */
        public readonly bool $canMigrateOnline,
    ) {}
}
