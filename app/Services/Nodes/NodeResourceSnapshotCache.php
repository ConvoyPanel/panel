<?php

namespace App\Services\Nodes;

use App\Data\Admin\Overview\NodeProcessorUsageData;
use App\Data\Admin\Overview\NodeResourceSnapshotData;
use App\Data\Admin\Overview\ResourceUsageData;
use App\Data\Cluster\NodeResourceData;
use App\Models\Node;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/** Current host resources written by the poller and never fetched on a read. */
class NodeResourceSnapshotCache
{
    public static function key(Node $node): string
    {
        return "node:{$node->id}:resource-snapshot";
    }

    public function put(Node $node, NodeResourceData $resource): void
    {
        Cache::put(
            self::key($node),
            new NodeResourceSnapshotData(
                cpu: new NodeProcessorUsageData(
                    count: $resource->cpuCount,
                    percent: $this->percentage($resource->cpuUsed * 100, 100),
                ),
                memory: new ResourceUsageData(
                    used: $resource->memoryUsed,
                    total: $resource->memoryTotal,
                    percent: $this->percentage($resource->memoryUsed, $resource->memoryTotal),
                ),
                disk: new ResourceUsageData(
                    used: $resource->diskUsed,
                    total: $resource->diskTotal,
                    percent: $this->percentage($resource->diskUsed, $resource->diskTotal),
                ),
                uptimeInSeconds: $resource->uptimeInSeconds,
                observedAt: CarbonImmutable::now(),
            ),
            now()->addMinutes(Node::STATUS_TTL_MINUTES),
        );
    }

    public function for(Node $node): ?NodeResourceSnapshotData
    {
        return Cache::get(self::key($node));
    }

    private function percentage(float|int $used, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(min(max(($used / $total) * 100, 0), 100), 2);
    }
}
