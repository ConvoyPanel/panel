<?php

namespace App\Data\Admin\Overview;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class NodeResourceSnapshotData extends Data
{
    public function __construct(
        public NodeProcessorUsageData $cpu,
        public ResourceUsageData $memory,
        public ResourceUsageData $disk,
        public int $uptimeInSeconds,
        public CarbonImmutable $observedAt,
    ) {}
}
