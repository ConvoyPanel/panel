<?php

namespace App\Services\Admin;

use App\Data\Admin\Overview\AddressUsageData;
use App\Data\Admin\Overview\BackupSummaryData;
use App\Data\Admin\Overview\FleetSummaryData;
use App\Data\Admin\Overview\IsoSummaryData;
use App\Data\Admin\Overview\MetricTrendData;
use App\Data\Admin\Overview\NodeSummaryData;
use App\Data\Admin\Overview\OverviewData;
use App\Data\Admin\Overview\OverviewTrendsData;
use App\Data\Admin\Overview\ResourceAllocationData;
use App\Data\Admin\Overview\ServerBreakdownData;
use App\Enums\Server\ServerLifecycle;
use App\Models\Address;
use App\Models\AddressBlockGroup;
use App\Models\Backup;
use App\Models\ISO;
use App\Models\Location;
use App\Models\Node;
use App\Models\Server;
use App\Models\Storage;
use App\Models\User;
use App\Services\Metrics\VictoriaMetrics;
use App\Services\Nodes\NodeResourceSnapshotCache;
use App\Support\ByteUnit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\DataCollection;

class OverviewService
{
    private const CACHE_KEY = 'admin:overview';

    private const CACHE_SECONDS = 15;

    public function __construct(
        private readonly VictoriaMetrics $metrics,
        private readonly NodeResourceSnapshotCache $resourceSnapshots,
    ) {}

    public function metrics(): OverviewData
    {
        /** @var OverviewData $data */
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn () => $this->build());

        // The DataCollection context is not preserved across cache serialization; re-apply the
        // endpoint contract so cached responses keep `nodes` as an array instead of `{ data: [] }`.
        $data->nodes->withoutWrapping();

        return $data;
    }

    /**
     * Flattened scalar metrics recorded to the time-series store. Names are prefixed so a single
     * range query (`convoy_overview_.+`) fetches them all back for trends.
     *
     * @return array<string, int|float>
     */
    public function snapshotMetrics(): array
    {
        $m = $this->metrics();

        return [
            'convoy_overview_servers' => $m->summary->servers,
            'convoy_overview_nodes' => $m->summary->nodes,
            'convoy_overview_users' => $m->summary->users,
            'convoy_overview_locations' => $m->summary->locations,
            'convoy_overview_failed_servers' => $m->summary->failedServers,
            'convoy_overview_memory_percent' => $m->memory->percent,
            'convoy_overview_storage_percent' => $m->storage->percent,
            'convoy_overview_addresses_assigned' => $m->addresses->assigned,
            'convoy_overview_backups_total' => $m->backups->total,
            'convoy_overview_backups_failed' => $m->backups->failed,
            'convoy_overview_isos_total' => $m->isos->total,
        ];
    }

    private function build(): OverviewData
    {
        $nodes = $this->loadNodes();
        $allocations = $this->loadServerAllocations();
        $lifecycles = $this->loadServerLifecycles();
        $suspended = $this->countSuspendedServers();

        return new OverviewData(
            generatedAt: CarbonImmutable::now(),
            summary: $this->summary($nodes, $lifecycles),
            servers: $this->servers($lifecycles, $suspended),
            memory: $this->memory($nodes, $allocations),
            storage: $this->storage($allocations),
            addresses: $this->addresses(),
            backups: $this->backups(),
            isos: $this->isos(),
            nodes: NodeSummaryData::collect(
                $nodes->map(fn (Node $node) => $this->node($node, $allocations))->values(),
                DataCollection::class,
            )->withoutWrapping(),
            trends: $this->trends(),
        );
    }

    private function trends(): OverviewTrendsData
    {
        // One range query over ~30 days (daily step) powers both the sparkline series and the delta.
        $series = $this->metrics->queryRange('{__name__=~"convoy_overview_.+"}', '-30d', 'now', '86400');

        return new OverviewTrendsData(
            servers: $this->trend($series, 'convoy_overview_servers'),
            nodes: $this->trend($series, 'convoy_overview_nodes'),
            users: $this->trend($series, 'convoy_overview_users'),
            backups: $this->trend($series, 'convoy_overview_backups_total'),
        );
    }

    /** @param  array<string, array<int, array{0: int, 1: float}>>  $series */
    private function trend(array $series, string $name): MetricTrendData
    {
        $points = $series[$name] ?? [];
        if ($points === []) {
            return new MetricTrendData(delta: null, series: []);
        }

        $values = array_map(fn (array $point): float => $point[1], $points);
        $current = end($values);

        // Delta vs. the sample nearest 7 days ago — but only once we hold ~a week of history, so a
        // fresh install shows no misleading delta.
        $delta = null;
        if ($points[0][0] <= now()->subDays(6)->getTimestamp()) {
            $target = now()->subDays(7)->getTimestamp();
            $nearest = $points[0][1];
            $bestDiff = PHP_INT_MAX;
            foreach ($points as [$timestamp, $value]) {
                $diff = abs($timestamp - $target);
                if ($diff < $bestDiff) {
                    $bestDiff = $diff;
                    $nearest = $value;
                }
            }
            $delta = round($current - $nearest, 2);
        }

        return new MetricTrendData(delta: $delta, series: array_values($values));
    }

    /** @return Collection<int, Node> */
    private function loadNodes(): Collection
    {
        return Node::query()
            ->select(['id', 'display_name', 'name', 'fqdn', 'memory', 'status', 'status_checked_at'])
            ->withCount('servers')
            ->orderBy('display_name')
            ->get();
    }

    /**
     * Per-node committed memory/disk. These are raw aggregates over the (MiB) DB columns —
     * StorageSizeCast is not applied to the SUM alias — so callers convert to bytes.
     *
     * @return Collection<int|string, \stdClass> keyed by node_id
     */
    private function loadServerAllocations(): Collection
    {
        return Server::query()
            ->toBase()
            ->select('node_id')
            ->selectRaw('COALESCE(SUM(memory), 0) as memory_allocated')
            ->selectRaw('COALESCE(SUM(disk), 0) as disk_allocated')
            ->groupBy('node_id')
            ->get()
            ->keyBy('node_id');
    }

    /** @return Collection<string, int>  lifecycle value => count */
    private function loadServerLifecycles(): Collection
    {
        return Server::query()
            ->toBase()
            ->select('lifecycle')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('lifecycle')
            ->get()
            ->mapWithKeys(fn (object $row) => [(string) $row->lifecycle => (int) $row->total]);
    }

    /**
     * Suspended servers, counted separately because suspension is not a lifecycle bucket.
     *
     * These rows are *also* counted under whatever lifecycle they're in, so this figure
     * overlaps the breakdown rather than partitioning it -- it must never be summed with
     * the lifecycle counts to get a total.
     */
    private function countSuspendedServers(): int
    {
        return Server::query()->whereNotNull('suspended_at')->count();
    }

    /** @param  Collection<int, Node>  $nodes */
    private function summary(Collection $nodes, Collection $lifecycles): FleetSummaryData
    {
        return new FleetSummaryData(
            servers: (int) $lifecycles->sum(),
            nodes: $nodes->count(),
            users: User::query()->count(),
            locations: Location::query()->count(),
            failedServers: $this->failedServers($lifecycles),
        );
    }

    private function servers(Collection $lifecycles, int $suspended): ServerBreakdownData
    {
        return new ServerBreakdownData(
            total: (int) $lifecycles->sum(),
            ready: (int) ($lifecycles[ServerLifecycle::READY->value] ?? 0),
            installing: (int) ($lifecycles[ServerLifecycle::INSTALLING->value] ?? 0),
            restoring: (int) ($lifecycles[ServerLifecycle::RESTORING_BACKUP->value] ?? 0),
            deleting: (int) ($lifecycles[ServerLifecycle::DELETING->value] ?? 0),
            failed: $this->failedServers($lifecycles),
            suspended: $suspended,
            lifecycles: $lifecycles->all(),
        );
    }

    private function failedServers(Collection $lifecycles): int
    {
        return (int) (
            ($lifecycles[ServerLifecycle::INSTALL_FAILED->value] ?? 0)
            + ($lifecycles[ServerLifecycle::DELETION_FAILED->value] ?? 0)
        );
    }

    /** @param  Collection<int, Node>  $nodes */
    private function memory(Collection $nodes, Collection $allocations): ResourceAllocationData
    {
        // $node->memory is StorageSizeCast (bytes); the allocation aggregate is raw (MiB).
        $allocated = ByteUnit::Mebibytes->toBytes((int) $allocations->sum('memory_allocated'));

        return $this->allocation($allocated, (int) $nodes->sum('memory'));
    }

    private function storage(Collection $allocations): ResourceAllocationData
    {
        $allocated = ByteUnit::Mebibytes->toBytes((int) $allocations->sum('disk_allocated'));

        /*
         * Total VM-disk capacity: distinct storages that back VM disks and are
         * attached to a node (storage no node reaches is not usable fleet
         * capacity). Distinct by row, so a shared pool counts once however many
         * nodes mount it.
         *
         * Prefers what the poll observed over what the operator typed. `size` is
         * a hand-entered figure that nothing checks and everything else on the
         * storage now reads from discovery; leaving the one fleet-wide number on
         * the declared value would make the dashboard disagree with every page
         * beneath it. It remains the fallback for a storage no node has reported
         * yet, which is the only case where it is the best answer available.
         */
        $total = (int) Storage::query()
            ->where('stores_kvm', true)
            ->whereHas('nodes')
            ->with('nodes')
            ->get()
            ->sum(fn (Storage $storage) => $storage->recordedCapacity()['total'] ?? $storage->size);

        return $this->allocation($allocated, $total);
    }

    private function addresses(): AddressUsageData
    {
        $total = Address::query()->count();
        $assigned = Address::query()->whereNotNull('server_id')->count();

        return new AddressUsageData(
            pools: AddressBlockGroup::query()->count(),
            total: $total,
            assigned: $assigned,
            available: max($total - $assigned, 0),
            percent: $this->percentage($assigned, $total),
        );
    }

    private function backups(): BackupSummaryData
    {
        return new BackupSummaryData(
            total: Backup::query()->count(),
            successful: Backup::query()->successful()->count(),
            pending: Backup::query()->whereNull('completed_at')->count(),
            failed: Backup::query()->whereNotNull('completed_at')->whereNotNull('error_code')->count(),
        );
    }

    private function isos(): IsoSummaryData
    {
        $total = ISO::query()->count();
        $successful = ISO::query()->where('is_successful', true)->count();

        return new IsoSummaryData(
            total: $total,
            successful: $successful,
            pending: max($total - $successful, 0),
        );
    }

    private function node(Node $node, Collection $allocations): NodeSummaryData
    {
        $row = $allocations->get($node->id);
        $allocated = ByteUnit::Mebibytes->toBytes((int) ($row->memory_allocated ?? 0));

        return new NodeSummaryData(
            id: $node->id,
            displayName: $node->display_name,
            name: $node->name,
            fqdn: $node->fqdn,
            servers: (int) $node->servers_count,
            status: $node->currentStatus(),
            memory: $this->allocation($allocated, (int) $node->memory),
            resources: $this->resourceSnapshots->for($node),
        );
    }

    private function allocation(int $allocated, int $total): ResourceAllocationData
    {
        return new ResourceAllocationData(
            allocated: $allocated,
            total: $total,
            percent: $this->percentage($allocated, $total),
        );
    }

    private function percentage(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
