<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Enums\Server\PowerAction;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Client\Servers\SendPowerCommandRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Services\Servers\VncService;
use Convoy\Transformers\Client\ServerDetailTransformer;
use Convoy\Transformers\Client\ServerStateTransformer;
use Convoy\Transformers\Client\ServerTerminalTransformer;
use Convoy\Transformers\Client\ServerTransformer;

class ServerController extends ApplicationApiController
{
    public function __construct(private VncService $vncService, private ServerDetailService $detailService, private ProxmoxServerRepository $serverRepository, private ProxmoxPowerRepository $powerRepository)
    {
    }

    public function index(Server $server)
    {
        return fractal($server, new ServerTransformer)->respond();
    }

    public function details(Server $server)
    {
        return fractal($this->detailService->getByProxmox($server), new ServerDetailTransformer)->respond();
    }

    public function getState(Server $server)
    {
        return fractal()->item($this->serverRepository->setServer($server)->getState(), new ServerStateTransformer)->respond();
    }

    public function updateState(Server $server, SendPowerCommandRequest $request)
    {
        $this->powerRepository->setServer($server)->send($request->enum('state', PowerAction::class));

        return $this->returnNoContent();
    }

    public function authorizeTerminal(Server $server)
    {
        $data = $this->vncService->generateCredentials($server);

        return fractal()->item([
            'token' => $data,
            'node' => $server->node->cluster,
            'vmid' => $server->vmid,
            'fqdn' => $server->node->fqdn,
            'port' => $server->node->port,
        ], new ServerTerminalTransformer)->respond();
    }
}
