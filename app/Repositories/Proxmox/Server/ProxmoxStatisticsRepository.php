<?php

namespace App\Repositories\Proxmox\Server;

use Carbon\CarbonImmutable;
use App\Data\Server\Proxmox\Usages\ServerDiskData;
use App\Data\Server\Proxmox\Usages\ServerNetworkData;
use App\Data\Server\Proxmox\Usages\ServerTimepointData;
use App\Enums\Server\StatisticConsolidatorFunction;
use App\Enums\Server\StatisticTimeRange;
use App\Models\Server;
use App\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Support\Arr;
use Spatie\LaravelData\DataCollection;
use Webmozart\Assert\Assert;

class ProxmoxStatisticsRepository extends ProxmoxRepository
{
    public function getStatistics(
        StatisticTimeRange            $from,
        StatisticConsolidatorFunction $consolidator = StatisticConsolidatorFunction::AVERAGE,
    ): DataCollection {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'server' => $this->server->vmid,
                         ])
                         ->get('/api2/json/nodes/{node}/qemu/{server}/rrddata', [
                             'timeframe' => $from->value,
                             'cf' => $consolidator->value,
                         ])
                         ->json();

        $test = Arr::map($this->getData($response), function (array $statistic) {
            return new ServerTimepointData(
                cpu_used   : $statistic['cpu'] ?? 0,
                memory_used: $statistic['mem'] ?? 0,
                network    : new ServerNetworkData(
                    in : $statistic['netin'] ?? 0,
                    out: $statistic['netout'] ?? 0,
                ),
                disk       : new ServerDiskData(
                    write: $statistic['diskwrite'] ?? 0,
                    read : $statistic['diskread'] ?? 0,
                ),
                timestamp  : CarbonImmutable::createFromTimestamp($statistic['time']),
            );
        });

        return ServerTimepointData::collection($test);
    }
}
