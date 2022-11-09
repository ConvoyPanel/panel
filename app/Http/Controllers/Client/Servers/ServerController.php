<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\SendPowerCommandRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\ServerStatusTransformer;
use Convoy\Transformers\Client\ServerTransformer;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerDetailService $detailService, private ProxmoxServerRepository $serverRepository, private ProxmoxPowerRepository $powerRepository)
    {
    }

    public function index(Server $server)
    {
        return fractal($this->detailService->getByEloquent($server), new ServerTransformer)->respond();
    }

    public function status(Server $server)
    {
        return fractal()->item($this->serverRepository->setServer($server)->getStatus(), new ServerStatusTransformer)->respond();
    }

    public function sendPowerCommand(Server $server, SendPowerCommandRequest $request)
    {
        $this->powerRepository->setServer($server);

        switch ($request->state) {
            case 'start':
                $this->powerRepository->send('start');
                break;
            case 'restart':
                $this->powerRepository->send('reboot');
                break;
            case 'kill':
                $this->powerRepository->send('stop');
                break;
            case 'shutdown':
                $this->powerRepository->send('shutdown');
                break;
        }

        return $this->returnNoContent();
    }
}
