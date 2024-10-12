<?php

namespace App\Http\Controllers\Client\Servers;

use App\Enums\Server\StatisticConsolidatorFunction;
use App\Enums\Server\StatisticTimeRange;
use App\Http\Requests\Client\Servers\GetStatisticRequest;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxStatisticsRepository;
use App\Transformers\Client\ServerTimepointDataTransformer;

class StatisticController
{
    public function __construct(private ProxmoxStatisticsRepository $statisticsRepository)
    {
    }

    public function __invoke(GetStatisticRequest $request, Server $server)
    {
        $from = $request->enum('from', StatisticTimeRange::class);
        $consolidator = $request->enum(
            'consolidator',
            StatisticConsolidatorFunction::class,
        ) ?? StatisticConsolidatorFunction::AVERAGE;

        $data = $this->statisticsRepository->setServer($server)->getStatistics(
            $from,
            $consolidator,
        );

        return fractal($data, new ServerTimepointDataTransformer())->respond();
    }
}
