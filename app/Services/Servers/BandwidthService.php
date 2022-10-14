<?php

namespace Convoy\Services\Servers;

use Carbon\Carbon;
use Convoy\Repositories\Proxmox\Server\ProxmoxMetricsRepository;
use Convoy\Services\ProxmoxService;

class BandwidthService extends ProxmoxService
{
    public function __construct(private ProxmoxMetricsRepository $repository)
    {
    }

    public function getMonthlyUsage()
    {
        $history = $this->repository->setServer($this->server)->getMetrics('month');

        $in = 0;
        $out = 0;

        foreach ($history as $metric) {
            if (Carbon::createFromTimestamp($metric['time'])->isCurrentMonth()) {
                $in += $metric['netin'] ?? 0;
                $out += $metric['netout'] ?? 0;
            }
        }

        return [
            'in' => $in,
            'out' => $out,
        ];
    }
}