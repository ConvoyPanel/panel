<?php

namespace App\Data\Admin\Overview;

use Spatie\LaravelData\Data;

/** The top-line counts shown across the overview header. */
class FleetSummaryData extends Data
{
    public function __construct(
        public int $servers,
        public int $nodes,
        /**
         * Accounts the operator provisioned. Guests are counted separately rather than folded in:
         * an install's customer count is what this number is read as, and a shared server would
         * otherwise inflate it.
         */
        public int $users,
        public int $guests,
        public int $locations,
        public int $failedServers,
        /** Cluster rows the identity tripwire flagged for a human (see ClusterIdentityService). */
        public int $flaggedClusters,
    ) {}
}
