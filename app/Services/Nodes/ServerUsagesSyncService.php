<?php

namespace Convoy\Services\Nodes;

use Carbon\Carbon;
use Convoy\Enums\Server\MetricTimeframe;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Node;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxStatisticsRepository;

class ServerUsagesSyncService
{
    public function __construct(private ProxmoxStatisticsRepository $repository)
    {
    }

    public function handle(Node $node)
    {
        $servers = $node->servers;

        $servers->each(function (Server $server) {
            try {
                $metrics = $this->repository->setServer($server)->getStatistics(
                    MetricTimeframe::HOUR,
                );

                $bandwidth = $server->bandwidth_usage;
                $endingDate = $server->hydrated_at ? Carbon::parse(
                    $server->hydrated_at,
                ) : Carbon::now()->firstOfMonth();

                foreach ($metrics as $metric) {
                    if (Carbon::createFromTimestamp($metric['time'])->gt($endingDate)) {
                        // we multiply it by 60 seconds because each metric is
                        // recorded every 1 minute but the values like netin and
                        // netout are in bytes/sec
                        $bandwidth += (int)$metric['netin'] * 60 + (int)$metric['netout'] * 60;
                    }
                }

                if ($bandwidth > 0) {
                    $server->update([
                        'bandwidth_usage' => $bandwidth,
                        'hydrated_at' => now(),
                    ]);
                }
            } catch (ProxmoxConnectionException $e) {
                // do nothing
            }
        });
    }
}
