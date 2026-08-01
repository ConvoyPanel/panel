<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Server\Proxmox\Usages\ServerTimepointData;
use App\Enums\Server\StatisticConsolidatorFunction;
use App\Enums\Server\StatisticTimeRange;
use App\Http\Requests\Client\Servers\GetStatisticRequest;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxStatisticsClient;
use Spatie\LaravelData\DataCollection;

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

        $timepoints = $this->statisticsClient->setServer($server)->getStatistics(
            $from,
            $consolidator,
        );

        /*
         * Wrap at the boundary, like every sibling controller does.
         *
         * `getStatistics()` returns a plain array because its other caller
         * (ServerUsagesSyncService) wants one. A plain array is not Responsable,
         * so returning it straight from here skipped laravel-data's `wrap`
         * config and put a bare JSON array on the wire -- while every other
         * client endpoint sends `{"data": [...]}`. The frontend reads
         * `{ data }` off every response, got `undefined` here, and threw on
         * `.map`, which surfaced as "the node did not return statistics" for a
         * request that had in fact succeeded.
         */
        return ServerTimepointData::collect($timepoints, DataCollection::class);
    }
}
