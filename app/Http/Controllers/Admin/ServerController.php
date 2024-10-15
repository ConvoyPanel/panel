<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Server\Status;
use App\Enums\Server\SuspensionAction;
use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\Servers\Settings\UpdateBuildRequest;
use App\Http\Requests\Admin\Servers\Settings\UpdateGeneralInfoRequest;
use App\Http\Requests\Admin\Servers\StoreServerRequest;
use App\Models\Filters\FiltersServerByAddressPoolId;
use App\Models\Filters\FiltersServerWildcard;
use App\Models\Server;
use App\Services\Servers\CloudinitService;
use App\Services\Servers\NetworkService;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\ServerDeletionService;
use App\Services\Servers\ServerSuspensionService;
use App\Services\Servers\SyncBuildService;
use App\Transformers\Admin\ServerBuildTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ServerController extends ApiController
{
    public function __construct(
        private ConnectionInterface $connection,
        private ServerDeletionService $deletionService,
        private NetworkService $networkService,
        private ServerSuspensionService $suspensionService,
        private ServerCreationService $creationService,
        private CloudinitService $cloudinitService,
        private SyncBuildService $buildModificationService,
    ) {
    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
                               ->with(['addresses', 'user', 'node'])
                               ->defaultSort('-id')
                               ->allowedFilters(
                                   [
                                       AllowedFilter::custom(
                                           '*',
                                           new FiltersServerWildcard(),
                                       ),
                                       AllowedFilter::custom(
                                           'address_pool_id',
                                           new FiltersServerByAddressPoolId(),
                                       ),
                                       AllowedFilter::exact('node_id'),
                                       AllowedFilter::exact('user_id'),
                                       'name',
                                   ],
                               )
                               ->paginate(min($request->query('per_page', 50), 100))->appends(
                                   $request->query(),
                               );

        return fractal($servers, new ServerBuildTransformer())->parseIncludes($request->include)
                                                              ->respond();
    }

    public function show(Request $request, Server $server)
    {
        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerBuildTransformer())->parseIncludes($request->include)
                                                             ->respond();
    }

    public function store(StoreServerRequest $request)
    {
        $server = $this->creationService->handle($request->validated());

        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerBuildTransformer())->parseIncludes(['user', 'node'])
                                                             ->respond();
    }

    public function update(UpdateGeneralInfoRequest $request, Server $server)
    {
        $this->connection->transaction(function () use ($request, $server) {
            if ($request->hostname !== $server->hostname && ! empty($request->hostname)) {
                try {
                    $this->cloudinitService->updateHostname($server, $request->hostname);
                } catch (ProxmoxConnectionException) {
                    throw new ServiceUnavailableHttpException(
                        message: "Server {$server->uuid} failed to sync hostname.",
                    );
                }
            }

            $server->update($request->validated());
        });

        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerBuildTransformer())->parseIncludes(['user', 'node'])
                                                             ->respond();
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

        return fractal($server, new ServerBuildTransformer())->parseIncludes(['user', 'node'])
                                                             ->respond();
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
