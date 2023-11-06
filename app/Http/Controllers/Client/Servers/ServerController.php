<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Models\Server;
use Illuminate\Http\JsonResponse;
use Convoy\Enums\Server\ConsoleType;
use Convoy\Enums\Server\PowerAction;
use Convoy\Services\Servers\VncService;
use Convoy\Services\Coterm\CotermJWTService;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Services\Servers\ServerConsoleService;
use Convoy\Transformers\Client\ServerTransformer;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Transformers\Client\ServerStateTransformer;
use Convoy\Transformers\Client\ServerDetailTransformer;
use Convoy\Transformers\Client\ServerTerminalTransformer;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Http\Requests\Client\Servers\SendPowerCommandRequest;
use Convoy\Http\Requests\Client\Servers\CreateConsoleSessionRequest;

class ServerController extends ApplicationApiController
{
    public function __construct(private CotermJWTService $cotermJWTService, private ServerConsoleService $consoleService, private ServerDetailService $detailService, private ProxmoxServerRepository $serverRepository, private ProxmoxPowerRepository $powerRepository)
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

    public function createConsoleSession(CreateConsoleSessionRequest $request, Server $server)
    {
        if ($server->node->coterm_enabled) {
            return new JsonResponse([
                'data' => [
                    'is_tls_enabled' => $server->node->coterm_tls_enabled,
                    'fqdn' => $server->node->coterm_fqdn,
                    'port' => $server->node->coterm_port,
                    'token' => $this->cotermJWTService->handle($server, $request->user(), $request->enum('type', ConsoleType::class))->toString(),
                ]
            ]);
        } else {
            $data = $this->consoleService->createConsoleUserCredentials($server);

            return fractal()->item([
                'ticket' => $data->ticket,
                'node' => $server->node->cluster,
                'vmid' => $server->vmid,
                'fqdn' => $server->node->fqdn,
                'port' => $server->node->port,
            ], new ServerTerminalTransformer)->respond();
        }
    }
}
