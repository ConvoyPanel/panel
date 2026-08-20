<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\PaginationMeta;
use App\Data\Server\Deployments\DeploymentData;
use App\Data\Server\ServerData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\ConsoleType;
use App\Enums\Server\DeploymentStatus;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerLifecycle;
use App\Facades\Audit;
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
        }

        // Deliberately no `nonCompleted()` filter on the running branch. A
        // deployment's terminal status and the server lifecycle it implies are
        // written in one transaction (ManagesDeploymentLifecycle::onComplete),
        // so scoping this to in-flight deployments meant the completed one was
        // never servable: the last poll before the commit still showed a step
        // running, and every poll after it 204'd. The progress screen could
        // therefore never show the finish — the one moment worth watching — and
        // instead emptied itself out while it waited to be replaced. The only
        // caller is that screen, and it renders solely for a server whose
        // lifecycle is transient, so "the latest deployment" is what it wants.

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

        Audit::record(AuditEvent::SERVER_INSTALLATION_RETRIED, subject: $server);

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
        $command = $request->enum('command', PowerCommand::class);

        $this->powerCommand->handle($server, $command);

        // Records the request, not the outcome: the command is dispatched asynchronously, and
        // whether it landed is deployment/task tracking's job. See docs/audit-log-plan.md.
        Audit::record(
            AuditEvent::SERVER_POWER_SENT,
            subject: $server,
            properties: ['command' => $command->value],
        );

        return response()->noContent();
    }

    public function createConsoleSession(CreateConsoleSessionRequest $request, Server $server)
    {
        $server->node->loadMissing('anchor.relay');

        $type = $request->enum('type', ConsoleType::class);

        $session = $this->anchorSession->create(
            server: $server,
            user: $request->user(),
            type: $type,
        );

        // Console access is the one client action that hands out an interactive shell, so it is
        // worth a line in the log even though it changes nothing.
        Audit::record(
            AuditEvent::SERVER_CONSOLE_SESSION_CREATED,
            subject: $server,
            properties: ['type' => $type->value],
        );

        return $session;
    }
}
