<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Enums\Server\StatisticConsolidatorFunction;
use Convoy\Enums\Server\StatisticTimeRange;
use Convoy\Http\Requests\Client\Servers\GetStatisticRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxStatisticsRepository;
use Convoy\Transformers\Client\ServerTimepointDataTransformer;

class StatisticController
{
    public function __construct(private ProxmoxStatisticsRepository $statisticsRepository)
    {
    }

    public function __invoke(GetStatisticRequest $request, Server $server)
    {
        $from = $request->enum('from', StatisticTimeRange::class);
        $consolidator = $request->enum(
            'consolidator', StatisticConsolidatorFunction::class,
        ) ?? StatisticConsolidatorFunction::AVERAGE;

        $data = $this->statisticsRepository->setServer($server)->getStatistics(
            $from, $consolidator,
        );

        return fractal($data, new ServerTimepointDataTransformer())->respond();
    }
}
