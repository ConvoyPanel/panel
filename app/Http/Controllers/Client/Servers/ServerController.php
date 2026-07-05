<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\PaginationMeta;
use App\Data\Server\Deployments\DeploymentData;
use App\Data\Server\ServerData;
use App\Data\Server\ServerTerminalData;
use App\Enums\Server\ConsoleType;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerStatus;
use App\Http\Requests\Client\Servers\CreateConsoleSessionRequest;
use App\Http\Requests\Client\Servers\RetryInstallationRequest;
use App\Http\Requests\Servers\SendPowerCommandRequest;
use App\Models\Server;
use App\Repositories\Proxmox\Server\ProxmoxServerRepository;
use App\Services\Coterm\CotermJWTService;
use App\Services\Servers\SendServerPowerCommand;
use App\Services\Servers\ServerConsoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

use function min;

class ServerController
{
    public function __construct(
        private CotermJWTService $cotermJWTService,
        private ServerConsoleService $consoleService,
        private ProxmoxServerRepository $serverRepository,
        private SendServerPowerCommand $powerCommand,
    ) {}

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query()->ownedBy($request->user()))
            ->allowedFilters(['name'])
            ->paginate(min($request->query('per_page', 50), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($servers, ServerData::class);
    }

    public function show(Server $server)
    {
        return ServerData::from($server);
    }

    public function getDeployment(Server $server)
    {
        $query = $server->deployments()->latest('requested_at');

        if ($server->status === ServerStatus::INSTALL_FAILED) {
            $query->where('status', DeploymentStatus::FAILED);
        } else {
            $query->nonCompleted();
        }

        $deployment = $query->with(['template', 'steps'])->first();

        if (! $deployment) {
            return response()->noContent();
        }

        return DeploymentData::from($deployment)->include('template', 'steps');
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
        return $this->serverRepository->setServer($server)->getState();
    }

    public function updateState(Server $server, SendPowerCommandRequest $request)
    {
        $this->powerCommand->handle($server, $request->enum('state', PowerCommand::class));

        return response()->noContent();
    }

    public function createConsoleSession(CreateConsoleSessionRequest $request, Server $server)
    {
        $server->node->loadMissing('coterm');

        if ($coterm = $server->node->coterm) {
            return new JsonResponse([
                'isTlsEnabled' => $coterm->is_tls_enabled,
                'fqdn' => $coterm->fqdn,
                'port' => $coterm->port,
                'token' => $this->cotermJWTService->handle(
                    $server,
                    $request->user(),
                    $request->enum('type', ConsoleType::class),
                )->toString(),
            ]);
        }

        $data = $this->consoleService->createConsoleUserCredentials($server);

        return new ServerTerminalData(
            ticket: $data->ticket,
            node: $server->node->name,
            vmid: $server->vmid,
            fqdn: $server->node->fqdn,
            port: $server->node->port,
        );
    }
}
