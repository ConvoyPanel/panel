<?php

namespace Convoy\Services\Nodes;

use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Services\Servers\NetworkService;

class ServerRateLimitsSyncService
{
    public function __construct(private NetworkService $service)
    {

    }

    public function handle(Node $node)
    {
        $servers = $node->servers;

        $servers->each(function (Server $server) {
            if ($server->bandwidth_usage >= $server->bandwidth_limit && isset($server->bandwidth_limit)) {
                $this->service->updateRateLimit($server, 1);
            } else {
                $this->service->updateRateLimit($server);
            }
        });
    }
}
