<?php

namespace App\Http\Controllers\Client\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Requests\Client\Servers\SendPowerCommandRequest;
use App\Models\Server;
use App\Services\Servers\PowerService;

class PowerController extends ApplicationApiController
{
    public function __construct(private PowerService $powerService)
    {

    }

    public function sendCommand(SendPowerCommandRequest $request, Server $server)
    {
        switch ($request->action) {
            case 'start':
                $this->powerService->setServer($server)->start();
                break;
            case 'shutdown':
                $this->powerService->setServer($server)->shutdown();
                break;
            case 'kill':
                $this->powerService->setServer($server)->kill();
                break;
            case 'reboot':
                $this->powerService->setServer($server)->reboot();
                break;
        }

        return $this->returnNoContent();
    }
}
