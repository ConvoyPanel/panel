<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxActivityRepository;
use App\Services\Servers\LogService;
use Illuminate\Http\Request;
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
