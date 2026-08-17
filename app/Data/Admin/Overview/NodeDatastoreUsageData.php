<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/** One datastore's capacity on a node, as of the last poll. */
class NodeDatastoreUsageData extends Data
{
    public function __construct(
        /** The PVE storage id, e.g. `local-lvm`. */
        public string $name,
        public ResourceUsageData $usage,
        /**
         * False when PVE could not read the store -- an unmounted NFS export
         * still appears in the listing, reporting zero. Rendering that as "0%
         * used" would be a comfortable lie, so the UI marks it instead.
         */
        public bool $online,
        /** Shared stores are counted against every node that mounts them. */
        public bool $shared,
    ) {}
}
