<?php

namespace App\Data\Admin\Overview;

use App\Enums\Node\NodeStatus;
use Spatie\LaravelData\Data;

/** Per-node row of the overview: identity, server count, and memory allocation. */
class NodeSummaryData extends Data
{
    public function __construct(
        public int $id,
        public string $displayName,
        public string $name,
        public string $fqdn,
        public int $servers,
        public NodeStatus $status,
        public ResourceAllocationData $memory,
        public ?NodeResourceSnapshotData $resources,
    ) {}
}
