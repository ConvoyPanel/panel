<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use Inertia\Inertia;

class LogsController extends ApplicationApiController
{
    public function __construct(private ProxmoxActivityRepository $repository)
    {
    }

    public function index(Server $server)
    {
        return Inertia::render('servers/logs/Index', [
            'server' => $server,
        ]);
    }

    public function getLogs(Server $server)
    {
        return $this->repository->setServer($server)->getLogs();
    }
}
