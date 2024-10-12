<?php

namespace App\Services\Nodes;

use Carbon\Carbon;
use App\Data\Server\Proxmox\Usages\ServerTimepointData;
use App\Enums\Server\StatisticTimeRange;
use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Models\Node;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxStatisticsRepository;

class ServerUsagesSyncService
{
    public function __construct(private ProxmoxStatisticsRepository $repository)
    {
    }

    public function handle(Node $node): void
    {
        $servers = $node->servers;

        $servers->each(function (Server $server) {
            try {
                $timepoints = $this->repository->setServer($server)->getStatistics(
                    StatisticTimeRange::HOUR_AGO,
                );

                $bandwidth = $server->bandwidth_usage;
                $endingDate = $server->hydrated_at ? Carbon::parse(
                    $server->hydrated_at,
                ) : Carbon::now()->firstOfMonth();

                foreach ($timepoints as $timepoint) {
                    /* @var ServerTimepointData $timepoint */
                    if ($timepoint->timestamp->gt($endingDate)) {
                        // we multiply it by 60 seconds because each metric is
                        // recorded every 1 minute but the values like netin and
                        // netout are in bytes/sec
                        $bandwidth += $timepoint->network->in * 60 + $timepoint->network->out * 60;
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
