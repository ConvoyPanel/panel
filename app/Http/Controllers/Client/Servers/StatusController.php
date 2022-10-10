<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;

/**
 * Class ServerStatusController
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
