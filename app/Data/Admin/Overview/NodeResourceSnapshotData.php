<?php

namespace App\Data\Admin\Overview;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class NodeResourceSnapshotData extends Data
{
    public function __construct(
        public NodeProcessorUsageData $cpu,
        public ResourceUsageData $memory,
        /** The host's root filesystem -- not the sum of its datastores. */
        public ResourceUsageData $disk,
        public int $uptimeInSeconds,
        public CarbonImmutable $observedAt,
        /**
         * Every datastore on this node summed into one figure.
         *
         * The dashboard shows a fleet at a glance, so it gets one number per
         * node: a host with a dozen datastores would otherwise turn a table row
         * into a dozen meters, and the card grows without bound. The per-store
         * breakdown belongs on the node's own Storages tab, where there is room
         * for it.
         *
         * Stores PVE could not read are excluded rather than counted as empty —
         * an unmounted export reports 0/0 and would quietly deflate the
         * percentage of everything around it. `$unreadableDatastores` is how the
         * UI says the total is incomplete.
         */
        public ResourceUsageData $storage,
        public int $datastoreCount,
        public int $unreadableDatastores,
        /**
         * Every datastore PVE reports for this node, most-full first.
         *
         * A `DataCollection` rather than a plain array of `Data`: an array gets
         * the `data` wrapper applied when it is serialised through the response,
         * so the JSON came out as `datastores.data[]` while the generated
         * TypeScript said `NodeDatastoreUsageData[]`. `OverviewData::$nodes`
         * already uses this shape and serialises flat.
         *
         * @var DataCollection<int, NodeDatastoreUsageData>
         */
        public DataCollection $datastores,
    ) {}
}
