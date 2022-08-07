<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Requests\Client\Servers\SendPowerCommandRequest;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use App\Services\Servers\PowerService;

class PowerController extends ApplicationApiController
{
    public function __construct(private ProxmoxPowerRepository $repository)
    {

    }

    public function sendCommand(SendPowerCommandRequest $request, Server $server)
    {
        $this->repository->setServer($server)->send($request->action);

        return $this->returnNoContent();
    }
}
