<?php

namespace Convoy\Repositories\Proxmox\Server;

use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\ProxmoxRepository;
use GuzzleHttp\Exception\GuzzleException;
use Webmozart\Assert\Assert;

class ProxmoxMetricsRepository extends ProxmoxRepository
{
    public static $timeframes = [
        'hour',
        'day',
        'week',
        'month',
        'year',
    ];

    // AP Statistics terminology lol
    public static $parameters = [
        'AVERAGE',
        'MAX',
    ];

    public function getMetrics(string $timeframe, string $parameter = 'AVERAGE')
    {
        Assert::isInstanceOf($this->server, Server::class);
        Assert::inArray($timeframe, self::$timeframes, 'Invalid timeframe');
        Assert::inArray($parameter, self::$parameters, 'Invalid parameter');

        try {
            $response = $this->getHttpClient()->get(sprintf('/api2/json/nodes/%s/qemu/%s/rrddata', $this->node->cluster, $this->server->vmid), [
                'query' => [
                    'timeframe' => $timeframe,
                    'cf' => $parameter,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ProxmoxConnectionException($e);
        }

        return $this->getData($response);
    }
}