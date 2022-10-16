<?php

namespace Convoy\Services\Servers;

use Carbon\Carbon;
use Convoy\Enums\Servers\ThrottleAction;
use Convoy\Repositories\Proxmox\Server\ProxmoxMetricsRepository;
use Convoy\Services\ProxmoxService;
use Illuminate\Support\Arr;

class BandwidthService extends ProxmoxService
{
    public function __construct(private ProxmoxMetricsRepository $repository)
    {
    }
}