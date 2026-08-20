<?php

namespace App\Http\Controllers\Admin;

use App\Data\PaginationMeta;
use App\Data\Server\ServerData;
use App\Enums\Audit\AuditEvent;
use App\Enums\Server\PowerCommand;
use App\Enums\Server\ServerLifecycle;
use App\Enums\Server\SuspensionAction;
use App\Exceptions\Proxmox\RequestException;
use App\Facades\Audit;
use App\Http\Requests\Admin\Servers\Settings\UpdateBuildRequest;
use App\Http\Requests\Admin\Servers\Settings\UpdateGeneralInfoRequest;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Http\Requests\Servers\SendPowerCommandRequest;
use App\Models\Filters\FiltersServerWildcard;
use App\Models\Server;
use App\Services\Proxmox\Server\ProxmoxServerClient;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\Power\ServerPowerLockService;
use App\Services\Servers\SendServerPowerCommand;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\ServerDeletionService;
use App\Services\Servers\ServerNetworkService;
use App\Services\Servers\ServerSuspensionService;
use App\Services\Servers\VmSyncService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ServerController
{
    public function __construct(
        private ConnectionInterface $connection,
        private ServerDeletionService $deletionService,
        private ServerNetworkService $networkService,
        private ServerSuspensionService $suspensionService,
        private ServerCreationService $creationService,
        private CloudinitService $cloudinitService,
        private VmSyncService $buildModificationService,
        private ProxmoxServerClient $serverClient,
        private SendServerPowerCommand $powerCommand,
        private ServerPowerLockService $powerLock,
    ) {}

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->with(['addresses', 'user', 'node'])
            ->defaultSort('-id')
            ->allowedFilters(
                [
                    AllowedFilter::custom('*', new FiltersServerWildcard),
                    AllowedFilter::exact('node_id'),
                    AllowedFilter::exact('user_id'),
                    'name',
                    'hostname',
                ],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($servers, ServerData::class);
    }

    public function show(Request $request, Server $server)
    {
        $server->load(['node']);

        return ServerData::from($server);
    }

    public function store(StoreServerRequest $request)
    {
        $server = $this->creationService->handle($request->validated());

        $server->load(['addresses', 'user', 'node']);

        Audit::record(
            AuditEvent::ADMIN_SERVER_CREATED,
            subject: $server,
            properties: ['name' => $server->name, 'node' => $server->node->name],
        );

        return ServerData::from($server);
    }

    public function update(UpdateGeneralInfoRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($request, $server) {
            if ($request->hostname !== $server->hostname && ! empty($request->hostname)) {
                try {
                    $this->cloudinitService->setHostname($server, $request->hostname);
                } catch (RequestException) {
                    throw new ServiceUnavailableHttpException(
                        message: "Server {$server->uuid} failed to sync hostname.",
                    );
                }
            }

            $server->update($request->validated());

            Audit::record(
                AuditEvent::ADMIN_SERVER_UPDATED,
                subject: $server,
                properties: ['changed' => array_keys($server->getChanges())],
            );
        });

        $server->load(['addresses', 'user', 'node']);

        return ServerData::from($server);
    }

    public function updateBuild(UpdateBuildRequest $request, Server $server)
    {
        $server->update($request->safe()->except('address_ids'));

        // Build/limit-only forms must not have to echo the server's addresses
        // back merely to preserve them. Only reconcile IP assignments when a
        // caller explicitly includes that part of the payload.
        if ($request->has('address_ids')) {
            $this->networkService->syncAddresses($server, $request->address_ids ?? []);
        }

        try {
            $this->buildModificationService->handle($server);
        } catch (RequestException $e) {
            // do nothing
        }

        // Resource limits are what a customer is billed on, so a change here belongs in their
        // server's feed as well as the admin log.
        Audit::record(
            AuditEvent::ADMIN_SERVER_BUILD_UPDATED,
            subject: $server,
            properties: ['changed' => array_keys($server->getChanges())],
        );

        $server->load(['addresses', 'user', 'node']);

        return ServerData::from($server);
    }

    public function suspend(Server $server)
    {
        $this->suspensionService->toggle($server);

        Audit::record(AuditEvent::ADMIN_SERVER_SUSPENDED, subject: $server);

        return response()->noContent();
    }

    public function unsuspend(Server $server)
    {
        $this->suspensionService->toggle($server, SuspensionAction::UNSUSPEND);

        Audit::record(AuditEvent::ADMIN_SERVER_UNSUSPENDED, subject: $server);

        return response()->noContent();
    }

    public function getState(Server $server)
    {
        $state = $this->serverClient->setServer($server)->getState();
        $state->pendingPowerAction = $this->powerLock->resolve($server);
        $state->lastPowerAction = $this->powerLock->result($server);

        return $state;
    }

    public function sendPowerCommand(SendPowerCommandRequest $request, Server $server)
    {
        $command = $request->enum('command', PowerCommand::class);

        $this->powerCommand->handle($server, $command);

        // A separate event from the client-side one: "staff power-cycled your server" and "you
        // power-cycled your server" read very differently in the owner's feed.
        Audit::record(
            AuditEvent::ADMIN_SERVER_POWER_SENT,
            subject: $server,
            properties: ['command' => $command->value],
        );

        return response()->noContent();
    }

    public function destroy(Request $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $server->update(['lifecycle' => ServerLifecycle::DELETING->value]);

            $properties = [
                'name' => $server->name,
                'uuid' => $server->uuid,
                'no_purge' => (bool) $request->input('no_purge', false),
            ];

            $this->deletionService->handle($server, $request->input('no_purge', false));

            // Retained forever: "who deleted this server" is one of the questions an audit log
            // exists to answer, and the subject morph will not resolve once the row is gone.
            Audit::record(
                AuditEvent::ADMIN_SERVER_DELETED,
                subject: $server,
                properties: $properties,
            );
        });

        return response()->noContent();
    }
}
