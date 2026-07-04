<?php

namespace App\Services\Nodes;

use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use App\Services\Servers\ServerNetworkBandwidthService;

class ServerRateLimitsSyncService
{
    public function __construct(private ServerNetworkBandwidthService $service)
    {
    }

    public function handle(Node $node): void
    {
        $servers = $node->servers;

        $servers->each(function (Server $server) {
            try {
                if ($server->bandwidth_usage >= $server->bandwidth_limit && isset($server->bandwidth_limit)) {
                    $this->service->setRateLimit($server, 1);
                } else {
                    $this->service->removeRateLimit($server);
                }
            } catch (ProxmoxConnectionException $e) {
                // do nothing
            }
        });
    }
}
