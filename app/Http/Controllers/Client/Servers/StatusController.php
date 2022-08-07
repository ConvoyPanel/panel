<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxAllocationRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Services\Servers\ResourceService;
use App\Services\Servers\StatusService;

/**
 * Class ServerStatusController
 * @package App\Http\Controllers\Client\Servers
 */
class StatusController extends ApplicationApiController
{
    public function __construct(private ProxmoxServerRepository $repository)
    {

    }

    public function show(Server $server)
    {
        return $this->repository->setServer($server)->getStatus();
    }
}