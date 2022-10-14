<?php

namespace Convoy\Services\Servers;

use Convoy\Repositories\Proxmox\Server\ProxmoxMetricsRepository;
use Convoy\Services\ProxmoxService;

class BandwidthService extends ProxmoxService
{
    public function __construct(private ProxmoxMetricsRepository $repository)
    {
    }

    public function getUsage(string $timeframe = 'month')
    {
        $history = $this->repository->setServer($this->server)->getMetrics($timeframe);

        $in = 0;
        $out = 0;

        foreach ($history as $metric) {
            $in += $metric['netin'] ?? 0;
            $out += $metric['netout'] ?? 0;
        }

        return [
            'in' => $in,
            'out' => $out,
        ];
    }
}