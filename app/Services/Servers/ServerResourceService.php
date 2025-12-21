<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxGuestAgentRepository;
use Illuminate\Support\Facades\Cache;

class ServerResourceService
{
    public function __construct(
        private ProxmoxGuestAgentRepository $repository
    ) {}

    public function getStorageUsage(Server $server): array
    {
        $cacheKey = "server.{$server->uuid}.storage_usage";

        return Cache::remember(key: $cacheKey, ttl: 60, callback: function () use ($server): array {
            $fsInfo = $this->repository->setServer($server)->getFsInfo();

            // Calculate total used bytes from all filesystems
            // We sum up all valid filesystems.
            $used = $fsInfo->sum('usedBytes');
            $total = $fsInfo->sum('totalBytes');

            return [
                'used_bytes' => $used,
                'total_bytes' => $total,
            ];
        });
    }
}
