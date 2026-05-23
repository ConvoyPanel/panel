<?php

namespace Convoy\Services\Admin;

use Convoy\Enums\Server\Status;
use Convoy\Models\ActivityLog;
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

    public function metrics(): array
    {
        return Cache::remember(
            'admin:overview:metrics',
            self::CACHE_SECONDS,
            fn () => $this->freshMetrics(),
        );
    }

    private function freshMetrics(): array
    {
        $nodes = Node::query()
            ->select(['id', 'name', 'cluster', 'fqdn', 'memory', 'disk'])
            ->withCount('servers')
            ->orderBy('name')
            ->get();

        $serverAllocations = Server::query()
            ->select('node_id')
            ->selectRaw('COALESCE(SUM(memory), 0) as memory_allocated')
            ->selectRaw('COALESCE(SUM(disk), 0) as disk_allocated')
            ->groupBy('node_id')
            ->get()
            ->keyBy('node_id');

        $serverStatuses = Server::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function (Server $server) {
                $status = $server->status ?? 'ready';

                return [$status => (int) $server->total];
            });

        $totalServers = (int) $serverStatuses->sum();
        $failedServers = (int) (
            ($serverStatuses[Status::INSTALL_FAILED->value] ?? 0)
            + ($serverStatuses[Status::DELETION_FAILED->value] ?? 0)
        );

        $memoryTotal = (int) $nodes->sum('memory');
        $memoryAllocated = (int) $serverAllocations->sum(
            fn ($allocation) => $this->mebibytesToBytes(
                (int) $allocation->memory_allocated,
            ),
        );
        $diskTotal = (int) $nodes->sum('disk');
        $diskAllocated = (int) $serverAllocations->sum(
            fn ($allocation) => $this->mebibytesToBytes(
                (int) $allocation->disk_allocated,
            ),
        );

        $addressStats = Address::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(server_id IS NOT NULL) as assigned')
            ->first();

        $backups = Backup::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(completed_at IS NOT NULL) as completed')
            ->selectRaw('SUM(is_successful = 1) as successful')
            ->selectRaw('SUM(completed_at IS NULL) as pending')
            ->first();

        $isos = ISO::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(is_successful = 1) as successful')
            ->first();

        $recentActivity = ActivityLog::query()
            ->with('actor')
            ->latest('timestamp')
            ->limit(8)
            ->get()
            ->map(fn (ActivityLog $activity) => [
                'id' => $activity->id,
                'event' => $activity->event,
                'description' => $activity->description,
                'actor' => $activity->actor?->getAttribute(
                    'name',
                ),
                'timestamp' => $activity->timestamp,
            ]);

        return [
            'generated_at' => now(),
            'summary' => [
                'servers' => $totalServers,
                'nodes' => $nodes->count(),
                'users' => User::query()->count(),
                'locations' => Location::query()->count(),
                'failed_servers' => $failedServers,
            ],
            'servers' => [
                'total' => $totalServers,
                'ready' => (int) ($serverStatuses['ready'] ?? 0),
                'installing' => (int) (
                    $serverStatuses[Status::INSTALLING->value] ?? 0
                ),
                'suspended' => (int) (
                    $serverStatuses[Status::SUSPENDED->value] ?? 0
                ),
                'restoring' => (int) (
                    ($serverStatuses[Status::RESTORING_BACKUP->value] ?? 0)
                    + (
                        $serverStatuses[Status::RESTORING_SNAPSHOT->value] ?? 0
                    )
                ),
                'deleting' => (int) (
                    $serverStatuses[Status::DELETING->value] ?? 0
                ),
                'failed' => $failedServers,
                'statuses' => $serverStatuses,
            ],
            'capacity' => [
                'memory' => [
                    'allocated' => $memoryAllocated,
                    'total' => $memoryTotal,
                    'percent' => $this->percentage(
                        $memoryAllocated,
                        $memoryTotal,
                    ),
                ],
                'disk' => [
                    'allocated' => $diskAllocated,
                    'total' => $diskTotal,
                    'percent' => $this->percentage(
                        $diskAllocated,
                        $diskTotal,
                    ),
                ],
            ],
            'addresses' => [
                'pools' => AddressPool::query()->count(),
                'total' => (int) $addressStats->total,
                'assigned' => (int) $addressStats->assigned,
                'available' => max(
                    (int) $addressStats->total - (int) $addressStats->assigned,
                    0,
                ),
                'percent' => $this->percentage(
                    (int) $addressStats->assigned,
                    (int) $addressStats->total,
                ),
            ],
            'backups' => [
                'total' => (int) $backups->total,
                'successful' => (int) $backups->successful,
                'pending' => (int) $backups->pending,
                'failed' => max(
                    (int) $backups->completed - (int) $backups->successful,
                    0,
                ),
            ],
            'isos' => [
                'total' => (int) $isos->total,
                'successful' => (int) $isos->successful,
                'pending' => max(
                    (int) $isos->total - (int) $isos->successful,
                    0,
                ),
            ],
            'nodes' => $nodes->map(
                fn (Node $node) => $this->nodeMetrics($node, $serverAllocations),
            ),
            'activity' => $recentActivity,
        ];
    }

    private function nodeMetrics(Node $node, Collection $serverAllocations): array
    {
        $allocation = $serverAllocations->get($node->id);
        $memoryAllocated = $this->mebibytesToBytes(
            (int) ($allocation->memory_allocated ?? 0),
        );
        $diskAllocated = $this->mebibytesToBytes(
            (int) ($allocation->disk_allocated ?? 0),
        );

        return [
            'id' => $node->id,
            'name' => $node->name,
            'cluster' => $node->cluster,
            'fqdn' => $node->fqdn,
            'servers' => (int) $node->servers_count,
            'memory' => [
                'allocated' => $memoryAllocated,
                'total' => $node->memory,
                'percent' => $this->percentage(
                    $memoryAllocated,
                    $node->memory,
                ),
            ],
            'disk' => [
                'allocated' => $diskAllocated,
                'total' => $node->disk,
                'percent' => $this->percentage(
                    $diskAllocated,
                    $node->disk,
                ),
            ],
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

        return round(min(($value / $total) * 100, 999.99), 2);
    }
}
