<?php

namespace App\Http\Controllers\Client\Servers;

use App\Enums\Server\ConsoleType;
use App\Enums\Server\PowerCommand;
use App\Http\Requests\Client\Servers\CreateConsoleSessionRequest;
use App\Http\Requests\Client\Servers\RetryInstallationRequest;
use App\Http\Requests\Client\Servers\SendPowerCommandRequest;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxPowerRepository;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Services\Coterm\CotermJWTService;
use App\Services\Servers\ServerConsoleService;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\ServerStatus;
use App\Transformers\Client\DeploymentTransformer;
use App\Transformers\Client\ServerStateTransformer;
use App\Transformers\Client\ServerTerminalTransformer;
use App\Transformers\Client\ServerTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

use function fractal;
use function min;

class ServerController
{
    public function __construct(
        private CotermJWTService $cotermJWTService,
        private ServerConsoleService $consoleService,
        private ProxmoxServerRepository $serverRepository,
        private ProxmoxPowerRepository $powerRepository,
    ) {}

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['name'])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return fractal($servers, new ServerTransformer)->respond();
    }

    public function show(Server $server)
    {
        return fractal($server, new ServerTransformer)->respond();
    }

    public function getDeployment(Server $server)
    {
        $query = $server->deployments()->latest('requested_at');

        if ($server->status === ServerStatus::INSTALL_FAILED) {
            $query->where('status', DeploymentStatus::FAILED);
        } else {
            $query->nonCompleted();
        }

        $deployment = $query->first();

        if (! $deployment) {
            return response()->noContent();
        }

        return fractal($deployment, new DeploymentTransformer)->respond();
    }

    public function retryInstallation(RetryInstallationRequest $request, Server $server)
    {
        $server->update([
            'status' => ServerStatus::DEFERRED_OS_SELECTION,
        ]);

        return response()->noContent();
    }

    public function getState(Server $server)
    {
        return fractal()->item(
            $this->serverRepository->setServer($server)->getState(),
            new ServerStateTransformer,
        )->respond();
    }

    public function updateState(Server $server, SendPowerCommandRequest $request)
    {
        $this->powerRepository->setServer($server)
            ->send($request->enum('state', PowerCommand::class));

        return response()->noContent();
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
                        $server,
                        $request->user(),
                        $request->enum('type', ConsoleType::class),
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
            ], new ServerTerminalTransformer)->respond();
        }
    }
}
