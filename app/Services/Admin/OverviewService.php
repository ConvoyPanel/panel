<?php

namespace Convoy\Services\Admin;

use Convoy\Enums\Server\Status;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Backup;
use Convoy\Models\ISO;
use Convoy\Models\Location;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OverviewService
{
    private const CACHE_SECONDS = 15;

    private const BYTES_PER_MEBIBYTE = 1048576;

    public function __construct(
        private VersionUpdateService $versionUpdateService,
    ) {}

    public function metrics(): array
    {
        return Cache::remember(
            'admin:overview:metrics',
            self::CACHE_SECONDS,
            fn () => $this->build(),
        );
    }

    private function build(): array
    {
        $nodes = $this->loadNodes();
        $allocations = $this->loadServerAllocations();
        $statuses = $this->loadServerStatuses();

        return [
            'generated_at' => now(),
            'update' => $this->versionUpdateService->status(),
            'summary' => $this->summary($nodes, $statuses),
            'servers' => $this->servers($statuses),
            'capacity' => $this->capacity($nodes, $allocations),
            'addresses' => $this->addresses(),
            'backups' => $this->backups(),
            'isos' => $this->isos(),
            'nodes' => $nodes
                ->map(fn (Node $node) => $this->node($node, $allocations))
                ->all(),
        ];
    }

    private function loadNodes(): Collection
    {
        return Node::query()
            ->select(['id', 'name', 'cluster', 'fqdn', 'memory', 'disk'])
            ->withCount('servers')
            ->orderBy('name')
            ->get();
    }

    private function loadServerAllocations(): Collection
    {
        return Server::query()
            ->select('node_id')
            ->selectRaw('COALESCE(SUM(memory), 0) as memory_allocated')
            ->selectRaw('COALESCE(SUM(disk), 0) as disk_allocated')
            ->groupBy('node_id')
            ->get()
            ->keyBy('node_id');
    }

    private function loadServerStatuses(): Collection
    {
        return Server::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn (Server $row) => [
                $row->status ?? 'ready' => (int) $row->total,
            ]);
    }

    private function summary(Collection $nodes, Collection $statuses): array
    {
        return [
            'servers' => (int) $statuses->sum(),
            'nodes' => $nodes->count(),
            'users' => User::query()->count(),
            'locations' => Location::query()->count(),
            'failed_servers' => $this->failedServers($statuses),
        ];
    }

    private function servers(Collection $statuses): array
    {
        return [
            'total' => (int) $statuses->sum(),
            'ready' => (int) ($statuses['ready'] ?? 0),
            'installing' => (int) ($statuses[Status::INSTALLING->value] ?? 0),
            'suspended' => (int) ($statuses[Status::SUSPENDED->value] ?? 0),
            'restoring' => (int) (
                ($statuses[Status::RESTORING_BACKUP->value] ?? 0)
                + ($statuses[Status::RESTORING_SNAPSHOT->value] ?? 0)
            ),
            'deleting' => (int) ($statuses[Status::DELETING->value] ?? 0),
            'failed' => $this->failedServers($statuses),
            'statuses' => $statuses->all(),
        ];
    }

    private function failedServers(Collection $statuses): int
    {
        return (int) (
            ($statuses[Status::INSTALL_FAILED->value] ?? 0)
            + ($statuses[Status::DELETION_FAILED->value] ?? 0)
        );
    }

    private function capacity(Collection $nodes, Collection $allocations): array
    {
        $memoryAllocated = $allocations->sum(
            fn ($row) => $this->mebibytesToBytes((int) $row->memory_allocated),
        );
        $diskAllocated = $allocations->sum(
            fn ($row) => $this->mebibytesToBytes((int) $row->disk_allocated),
        );

        return [
            'memory' => $this->metric($memoryAllocated, (int) $nodes->sum('memory')),
            'disk' => $this->metric($diskAllocated, (int) $nodes->sum('disk')),
        ];
    }

    private function addresses(): array
    {
        $stats = Address::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(server_id IS NOT NULL) as assigned')
            ->first();

        $total = (int) $stats->total;
        $assigned = (int) $stats->assigned;

        return [
            'pools' => AddressPool::query()->count(),
            'total' => $total,
            'assigned' => $assigned,
            'available' => max($total - $assigned, 0),
            'percent' => $this->percentage($assigned, $total),
        ];
    }

    private function backups(): array
    {
        $stats = Backup::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(completed_at IS NOT NULL) as completed')
            ->selectRaw('SUM(is_successful = 1) as successful')
            ->selectRaw('SUM(completed_at IS NULL) as pending')
            ->first();

        return [
            'total' => (int) $stats->total,
            'successful' => (int) $stats->successful,
            'pending' => (int) $stats->pending,
            'failed' => max((int) $stats->completed - (int) $stats->successful, 0),
        ];
    }

    private function isos(): array
    {
        $stats = ISO::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(is_successful = 1) as successful')
            ->first();

        $total = (int) $stats->total;
        $successful = (int) $stats->successful;

        return [
            'total' => $total,
            'successful' => $successful,
            'pending' => max($total - $successful, 0),
        ];
    }

    private function node(Node $node, Collection $allocations): array
    {
        $row = $allocations->get($node->id);
        $memoryAllocated = $this->mebibytesToBytes((int) ($row->memory_allocated ?? 0));
        $diskAllocated = $this->mebibytesToBytes((int) ($row->disk_allocated ?? 0));

        return [
            'id' => $node->id,
            'name' => $node->name,
            'cluster' => $node->cluster,
            'fqdn' => $node->fqdn,
            'servers' => (int) $node->servers_count,
            'memory' => $this->metric($memoryAllocated, (int) $node->memory),
            'disk' => $this->metric($diskAllocated, (int) $node->disk),
        ];
    }

    private function metric(int $allocated, int $total): array
    {
        return [
            'allocated' => $allocated,
            'total' => $total,
            'percent' => $this->percentage($allocated, $total),
        ];
    }

    private function mebibytesToBytes(int $value): int
    {
        return $value * self::BYTES_PER_MEBIBYTE;
    }

    private function percentage(int $value, int $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
