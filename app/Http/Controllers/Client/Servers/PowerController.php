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
        $this->repository->setServer($server);

        switch ($request->action) {
            case 'start':
                $this->repository->send('start');
                break;
            case 'reboot':
                $this->repository->send('reboot');
                break;
            case 'kill':
                $this->repository->send('stop');
                break;
            case 'shutdown':
                $this->repository->send('shutdown');
                break;
        }

        return $this->returnNoContent();
    }
}
