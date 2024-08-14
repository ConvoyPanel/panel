<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Enums\Server\MetricParameter;
use Convoy\Enums\Server\MetricTimeframe;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use Illuminate\Support\Arr;
use Webmozart\Assert\Assert;

class ProxmoxStatisticsRepository extends ProxmoxRepository
{
    public function getStatistics(
        MetricTimeframe $timeframe, MetricParameter $parameter = MetricParameter::AVERAGE,
    ): array
    {
        Assert::isInstanceOf($this->server, Server::class);

        $response = $this->getHttpClient()
                         ->withUrlParameters([
                             'node' => $this->node->cluster,
                             'server' => $this->server->vmid,
                         ])
                         ->get('/api2/json/nodes/{node}/qemu/{server}/rrddata', [
                             'timeframe' => $timeframe->value,
                             'cf' => $parameter->value,
                         ])
                         ->json();

        return Arr::map($this->getData($response), function (array $metric) {
            $metric['netin'] = array_key_exists('netin', $metric) ? intval(
                floor($metric['netin']),
            ) : 0;
            $metric['netout'] = array_key_exists('netout', $metric) ? intval(
                floor($metric['netout']),
            ) : 0;

            return $metric;
        });
    }
}
