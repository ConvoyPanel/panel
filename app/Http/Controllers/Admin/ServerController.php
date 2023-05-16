<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Enums\Server\Status;
use Convoy\Enums\Server\SuspensionAction;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Servers\Settings\UpdateBuildRequest;
use Convoy\Http\Requests\Admin\Servers\Settings\UpdateGeneralInfoRequest;
use Convoy\Http\Requests\Admin\Servers\StoreServerRequest;
use Convoy\Models\Filters\FiltersServer;
use Convoy\Models\Server;
use Convoy\Services\Servers\CloudinitService;
use Convoy\Services\Servers\NetworkService;
use Convoy\Services\Servers\ServerCreationService;
use Convoy\Services\Servers\ServerDeletionService;
use Convoy\Services\Servers\ServerSuspensionService;
use Convoy\Services\Servers\SyncBuildService;
use Convoy\Transformers\Admin\ServerTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends ApplicationApiController
{
    public function __construct(private ConnectionInterface $connection, private ServerDeletionService $deletionService, private NetworkService $networkService, private ServerSuspensionService $suspensionService, private ServerCreationService $creationService, private CloudinitService $cloudinitService, private SyncBuildService $buildModificationService)
    {
    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->with(['addresses', 'user', 'node'])
            ->allowedFilters([AllowedFilter::custom('*', new FiltersServer), AllowedFilter::exact('node_id'), AllowedFilter::exact('user_id'), 'name'])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($servers, new ServerTransformer())->parseIncludes($request->includes)->respond();
    }

    public function show(Request $request, Server $server)
    {
        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerTransformer())->parseIncludes($request->includes)->respond();
    }

    public function store(StoreServerRequest $request)
    {
        $server = $this->creationService->handle($request->validated());

        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerTransformer())->parseIncludes(['user', 'node'])->respond();
    }

    public function update(UpdateGeneralInfoRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($request, $server) {
            if ($request->hostname !== $server->hostname && !empty($request->hostname)) {
                try {
                    $this->cloudinitService->updateHostname($server, $request->hostname);
                } catch (ProxmoxConnectionException) {
                    // do nothing
                }
            }

            if ($request->node_id !== $server->node_id && !empty($request->node_id)) {
                // TODO: automatically drop linked IPs and validation the server has enough storage (validation will prob go in rule)
                $server->addresses()->update([
                    'server_id' => null
                ]);
            }

            $server->update($request->validated());
        });

        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerTransformer)->parseIncludes(['user', 'node'])->respond();
    }

    public function updateBuild(UpdateBuildRequest $request, Server $server)
    {
        $server->update($request->safe()->except('address_ids'));

        $this->networkService->updateAddresses($server, $request->address_ids ?? []);

        try {
            $this->buildModificationService->handle($server);
        } catch (ProxmoxConnectionException $e) {
            // do nothing
        }

        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerTransformer)->parseIncludes(['user', 'node'])->respond();
    }

    public function suspend(Server $server)
    {
        $this->suspensionService->toggle($server);

        return $this->returnNoContent();
    }

    public function unsuspend(Server $server)
    {
        $this->suspensionService->toggle($server, SuspensionAction::UNSUSPEND);

        return $this->returnNoContent();
    }

    public function destroy(Request $request, Server $server)
    {
        $this->connection->transaction(function () use ($server, $request) {
            $server->update(['status' => Status::DELETING->value]);

            $this->deletionService->handle($server, $request->input('no_purge', false));
        });

        return $this->returnNoContent();
    }
}
