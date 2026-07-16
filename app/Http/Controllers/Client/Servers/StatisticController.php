<?php

namespace App\Http\Controllers\Client\Servers;

use App\Enums\Server\StatisticConsolidatorFunction;
use App\Enums\Server\StatisticTimeRange;
use App\Http\Requests\Client\Servers\GetStatisticRequest;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxStatisticsClient;

class StatisticController
{
    public function __construct(private ProxmoxStatisticsClient $statisticsClient) {}

    public function __invoke(GetStatisticRequest $request, Server $server)
    {
        $from = $request->enum('from', StatisticTimeRange::class);
        $consolidator = $request->enum(
            'consolidator',
            StatisticConsolidatorFunction::class,
        ) ?? StatisticConsolidatorFunction::AVERAGE;

        return $this->statisticsClient->setServer($server)->getStatistics(
            $from,
            $consolidator,
        );
    }
}
