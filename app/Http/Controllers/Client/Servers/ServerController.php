<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\PaginationMeta;
use App\Data\Server\Deployments\DeploymentData;
use App\Data\Server\ServerData;
use App\Enums\Server\ConsoleType;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerLifecycle;
use App\Http\Requests\Client\Servers\CreateConsoleSessionRequest;
use App\Http\Requests\Client\Servers\RetryInstallationRequest;
use App\Http\Requests\Servers\SendPowerCommandRequest;
use App\Models\Server;
use App\Services\Anchor\AnchorSessionService;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use App\Services\Servers\Power\ServerPowerLockService;
use App\Services\Servers\SendServerPowerCommand;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

use function min;

class ServerController
{
    public function __construct(
        private AnchorSessionService $anchorSession,
        private ProxmoxServerClient $serverClient,
        private SendServerPowerCommand $powerCommand,
        private ServerPowerLockService $powerLock,
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

        if ($server->lifecycle === ServerLifecycle::INSTALL_FAILED) {
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
            'lifecycle' => ServerLifecycle::DEFERRED_OS_SELECTION,
        ]);

        return response()->noContent();
    }

    public function getState(Server $server)
    {
        $state = $this->serverClient->setServer($server)->getState();
        $state->pendingPowerAction = $this->powerLock->resolve($server);
        $state->lastPowerAction = $this->powerLock->result($server);

        return $state;
    }

    public function sendPowerCommand(Server $server, SendPowerCommandRequest $request)
    {
        $this->powerCommand->handle($server, $request->enum('command', PowerCommand::class));

        return response()->noContent();
    }

    public function createConsoleSession(CreateConsoleSessionRequest $request, Server $server)
    {
        $server->node->loadMissing('anchor.relay');

        return $this->anchorSession->create(
            server: $server,
            user: $request->user(),
            type: $request->enum('type', ConsoleType::class),
        );
    }
}
