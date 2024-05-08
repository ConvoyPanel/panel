<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Enums\Server\ConsoleType;
use Convoy\Enums\Server\PowerAction;
use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Client\Servers\CreateConsoleSessionRequest;
use Convoy\Http\Requests\Client\Servers\SendPowerCommandRequest;
use Convoy\Models\Server;
use Convoy\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use Convoy\Repositories\Proxmox\Server\ProxmoxServerRepository;
use Convoy\Services\Coterm\CotermJWTService;
use Convoy\Services\Servers\ServerConsoleService;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Services\Servers\VncService;
use Convoy\Transformers\Client\OldServerTransformer;
use Convoy\Transformers\Client\ServerDetailTransformer;
use Convoy\Transformers\Client\ServerStateTransformer;
use Convoy\Transformers\Client\ServerTerminalTransformer;
use Convoy\Transformers\Client\ServerTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use function fractal;
use function min;

class ServerController extends ApiController
{
    public function __construct(
        private CotermJWTService        $cotermJWTService,
        private ServerConsoleService    $consoleService,
        private ServerDetailService     $detailService,
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxPowerRepository  $powerRepository,
    )
    {
    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
                               ->allowedFilters(['name'])
                               ->paginate(min($request->query('per_page', 50), 100))
                               ->appends($request->query());

        return fractal($servers, new ServerTransformer())->respond();
    }

    public function show(Server $server)
    {
        return fractal($server, new OldServerTransformer())->respond();
    }

    public function details(Server $server)
    {
        return fractal(
            $this->detailService->getByProxmox($server), new ServerDetailTransformer(),
        )->respond();
    }

    public function getState(Server $server)
    {
        return fractal()->item(
            $this->serverRepository->setServer($server)->getState(), new ServerStateTransformer(),
        )->respond();
    }

    public function updateState(Server $server, SendPowerCommandRequest $request)
    {
        $this->powerRepository->setServer($server)
                              ->send($request->enum('state', PowerAction::class));

        return $this->returnNoContent();
    }

    public function createConsoleSession(CreateConsoleSessionRequest $request, Server $server)
    {
        $server->node->loadMissing('coterm');

        if ($coterm = $server->node->coterm) {
            return new JsonResponse([
                'data' => [
                    'is_tls_enabled' => $coterm->is_tls_enabled,
                    'fqdn' => $coterm->fqdn,
                    'port' => $coterm->port,
                    'token' => $this->cotermJWTService->handle(
                        $server, $request->user(), $request->enum('type', ConsoleType::class),
                    )
                                                      ->toString(),
                ],
            ]);
        } else {
            $data = $this->consoleService->createConsoleUserCredentials($server);

            return fractal()->item([
                'ticket' => $data->ticket,
                'node' => $server->node->cluster,
                'vmid' => $server->vmid,
                'fqdn' => $server->node->fqdn,
                'port' => $server->node->port,
            ], new ServerTerminalTransformer())->respond();
        }
    }
}
