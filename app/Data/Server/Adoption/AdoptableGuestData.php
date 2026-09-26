<?php

namespace App\Data\Server\Adoption;

use Spatie\LaravelData\Data;

/**
 * A QEMU guest that exists on a registered node and that Convoy does not own.
 *
 * Derived, never stored: `/cluster/resources` minus the servers table by
 * (cluster, vmid). A table of these would be a cache of a cache, stale the
 * moment someone deletes a VM in the PVE web UI.
 */
class AdoptableGuestData extends Data
{
    public function __construct(
        public readonly int $nodeId,
        public readonly string $nodeName,
        public readonly int $vmid,
        public readonly ?string $name,
        /** PVE's word for it: `running`, `stopped`, … */
        public readonly string $status,
        public readonly int $cpuCount,
        public readonly int $memory,
        public readonly int $diskSize,
        public readonly int $uptimeInSeconds,
        /**
         * Why this guest cannot be adopted right now, or null. A locked guest
         * is mid-operation and its config is not a stable thing to read.
         */
        public readonly ?string $blockedReason,
    ) {}
}
