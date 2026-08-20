<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/** The top-line counts shown across the overview header. */
class FleetSummaryData extends Data
{
    public function __construct(
        public int $servers,
        public int $nodes,
        public int $users,
        public int $locations,
        public int $failedServers,
        /** Cluster rows the identity tripwire flagged for a human (see ClusterIdentityService). */
        public int $flaggedClusters,
    ) {}
}
