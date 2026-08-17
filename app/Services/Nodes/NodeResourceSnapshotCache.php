<?php

namespace App\Services\Nodes;

use App\Data\Admin\Overview\NodeDatastoreUsageData;
use App\Data\Admin\Overview\NodeProcessorUsageData;
use App\Data\Admin\Overview\NodeResourceSnapshotData;
use App\Data\Admin\Overview\ResourceUsageData;
use App\Data\Cluster\NodeResourceData;
use App\Data\Cluster\StorageResourceData;
use App\Models\Node;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\DataCollection;

/** Current host resources written by the poller and never fetched on a read. */
class NodeResourceSnapshotCache
{
    /**
     * The `:v2` suffix retires entries written before `datastores` existed.
     *
     * Cached `Data` objects are unserialized without running the constructor, so
     * a promoted property's default never applies to a row written by the old
     * shape -- it comes back uninitialized and throws on first read. A new key
     * lets those simply expire instead, which costs one poll interval of
     * `unknown` on deploy and nothing else.
     */
    public static function key(Node $node): string
    {
        return "node:{$node->id}:resource-snapshot:v2";
    }

    /**
     * @param  Collection<int, StorageResourceData>  $storages  this node's datastores; empty is a
     *                                                          legitimate answer, not a failure
     */
    public function put(Node $node, NodeResourceData $resource, Collection $storages = new Collection): void
    {
        $datastores = $this->datastores($storages);
        $readable = collect($datastores->items())
            ->filter(fn (NodeDatastoreUsageData $datastore) => $datastore->online);

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
                storage: $this->aggregate($readable),
                datastoreCount: $datastores->count(),
                unreadableDatastores: $datastores->count() - $readable->count(),
                datastores: $datastores,
            ),
            now()->addMinutes(Node::STATUS_TTL_MINUTES),
        );
    }

    /**
     * Every readable datastore summed into one used/total pair.
     *
     * A sum of raw bytes, not a mean of percentages: averaging the percentages
     * would let a full 10 GiB scratch store weigh as heavily as a half-empty
     * 20 TiB array, which is exactly backwards.
     *
     * @param  Collection<int, NodeDatastoreUsageData>  $readable
     */
    private function aggregate(Collection $readable): ResourceUsageData
    {
        $used = (int) $readable->sum(fn (NodeDatastoreUsageData $datastore) => $datastore->usage->used);
        $total = (int) $readable->sum(fn (NodeDatastoreUsageData $datastore) => $datastore->usage->total);

        return new ResourceUsageData(
            used: $used,
            total: $total,
            percent: $this->percentage($used, $total),
        );
    }

    /**
     * @param  Collection<int, StorageResourceData>  $storages
     * @return DataCollection<int, NodeDatastoreUsageData>
     */
    private function datastores(Collection $storages): DataCollection
    {
        $datastores = $storages
            ->map(fn (StorageResourceData $storage) => new NodeDatastoreUsageData(
                name: $storage->name,
                usage: new ResourceUsageData(
                    used: $storage->used,
                    total: $storage->total,
                    percent: $this->percentage($storage->used, $storage->total),
                ),
                // PVE says `available` when it could actually read the store.
                // Anything else (typically `unknown`) means the figures beside
                // it are not to be trusted.
                online: $storage->status === 'available',
                shared: $storage->shared,
            ))
            // Fullest first: on a card that shows every store, the one about to
            // run out is the only one worth reading at a glance.
            ->sortByDesc(fn (NodeDatastoreUsageData $datastore) => $datastore->usage->percent)
            ->values()
            ->all();

        return NodeDatastoreUsageData::collect($datastores, DataCollection::class);
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
