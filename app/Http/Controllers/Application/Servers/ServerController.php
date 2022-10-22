<?php

namespace Convoy\Http\Controllers\Application\Servers;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Application\Servers\StoreServerRequest;
use Convoy\Http\Requests\Application\Servers\UpdateDetailsRequest;
use Convoy\Http\Requests\Application\Servers\UpdateServerRequest;
use Convoy\Models\Objects\Server\ServerDeploymentObject;
use Convoy\Models\Objects\Server\ServerSpecificationsObject;
use Convoy\Models\Server;
use Convoy\Services\Servers\BuildService;
use Convoy\Services\Servers\NetworkService;
use Convoy\Services\Servers\ServerCreationService;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Services\Servers\ServerUpdateService;
use Convoy\Transformers\Application\ServerTransformer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerCreationService $creationService, private NetworkService $networkService, private ServerDetailService $detailService, private ServerUpdateService $updateService, private BuildService $buildService)
    {
    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['status', 'uuid', 'uuidShort', 'name', 'user_id', 'node_id', 'vmid'])
            ->allowedSorts(['id', 'user_id', 'node_id', 'vmid'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($servers, new ServerTransformer())->respond();
    }

    public function store(StoreServerRequest $request)
    {

        $server = $this->creationService->handle(ServerDeploymentObject::from($request->validated()));

        return fractal($server, new ServerTransformer())->respond();
    }

    public function show(Server $server)
    {
        return fractal($server, new ServerTransformer())->respond();
    }

    public function destroy(Server $server, Request $request)
    {
        if (empty($request->no_purge)) {
            try {
                $this->buildService->setServer($server)->delete();
            } catch (Exception $e) {
            }
        }

        $server->delete();

        return $this->returnNoContent();
    }

    public function update(Server $server, UpdateServerRequest $request)
    {
        $server = $server->update($request->validated());

        return fractal($server, new ServerTransformer())->respond();
    }

    public function getDetails(Server $server)
    {
        return $this->detailService->setServer($server)->getDetails();
    }

    public function updateDetails(Server $server, UpdateDetailsRequest $request)
    {
        $data = $request->validated();

        $deployment = [
            'limits' => [
                'cpu' => Arr::get($data, 'limits.cpu'),
                'memory' => Arr::get($data, 'limits.memory'),
                'disk' => Arr::get($data, 'limits.disk'),
                // TODO: make eloquent mark these as used
                'addresses' => $this->networkService->convertFromEloquent(Arr::get($data, 'limits.addresses_ids', [])),
            ],
        ];
        $server->update($request->safe()->only(['cpu', 'memory', 'disk']));

        $this->updateService->setServer($server)->handle();

        return $this->detailService->setServer($server)->getDetails();
    }
}
