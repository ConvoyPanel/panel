<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Models\Server;
use App\Services\Servers\ResourceService;
use App\Services\Servers\StatusService;

/**
 * Class ServerStatusController
 * @package App\Http\Controllers\Client\Servers
 */
class StatusController extends ApplicationApiController
{
    /**
     * ServerStatusController constructor.
     * @param Server $server
     * @param StatusService $proxmoxService
     */
    public function __construct(private StatusService $proxmoxService, private ResourceService $resourceService)
    {

    }

    public function show(Server $server)
    {
        return $this->returnContent($this->proxmoxService->setServer($server)->fetchStatus());
    }

    public function getResources(Server $server)
    {
        return $this->resourceService->setServer($server)->getResources();
    }
}