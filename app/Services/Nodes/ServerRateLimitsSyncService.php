<?php

namespace App\Services\Nodes;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use App\Services\Servers\NetworkService;

class ServerRateLimitsSyncService
{
    public function __construct(private NetworkService $service)
    {
    }

    public function handle(Node $node)
    {
        $servers = $node->servers;

        $servers->each(function (Server $server) {
            try {
                if ($server->bandwidth_usage >= $server->bandwidth_limit && isset($server->bandwidth_limit)) {
                    $this->service->updateRateLimit($server, 1);
                } else {
                    $this->service->updateRateLimit($server);
                }
            } catch (ProxmoxConnectionException $e) {
                // do nothing
            }
        });
    }
}
