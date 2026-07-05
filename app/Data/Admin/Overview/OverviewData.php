<?php

namespace App\Data\Admin\Overview;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

class OverviewData extends Data
{
    public function __construct(
        public CarbonImmutable $generatedAt,
        public FleetSummaryData $summary,
        public ServerStatusBreakdownData $servers,
        /** Fleet memory: committed VM memory vs. node capacity. */
        public ResourceAllocationData $memory,
        /** Fleet storage: committed VM disk vs. VM-disk storage capacity. */
        public ResourceAllocationData $storage,
        public AddressUsageData $addresses,
        public BackupSummaryData $backups,
        public IsoSummaryData $isos,
        /** @var DataCollection<int, NodeSummaryData> $nodes */
        public DataCollection $nodes,
    ) {}
}
