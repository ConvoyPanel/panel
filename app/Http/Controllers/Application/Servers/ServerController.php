<?php

namespace App\Http\Controllers\Application\Servers;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\Servers\StoreServerRequest;
use App\Http\Requests\Application\Servers\UpdateServerRequest;
use App\Http\Requests\Application\Servers\UpdateDetailsRequest;
use App\Models\Server;
use App\Services\Servers\ServerCreationService;
use App\Services\Servers\InstallService;
use App\Services\Servers\NetworkService;
use App\Services\Servers\ResourceService;
use App\Services\Servers\ServerUpdateService;
use App\Transformers\Application\ServerTransformer;
use App\Transformers\Application\SpecificationTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends ApplicationApiController
{
    public function __construct(private ServerCreationService $creationService, private ServerUpdateService $updateService)
    {

    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->allowedFilters(['user_id', 'node_id', 'vmid', 'name', 'description', 'installing'])
            ->allowedSorts(['id', 'user_id', 'node_id', 'vmid'])
            ->paginate($request->query('per_page') ?? 50);

        return fractal($servers, new ServerTransformer())->respond();
    }

    public function store(StoreServerRequest $request)
    {
        $server = $this->creationService->handle($request->type);

        return fractal($server, new ServerTransformer())->respond();
    }

    public function show(Server $server)
    {
        return fractal($server, new ServerTransformer())->respond();
    }

    public function destroy(Server $server, Request $request)
    {
        if ($request->purge === true) {
            $this->installService->setServer($server)->delete();
        }

        $server->delete();

        return $this->returnNoContent();
    }

    public function update(Server $server, UpdateServerRequest $request)
    {
        $server = $server->update($request->validated());

        return fractal($server, new ServerTransformer())->respond();
    }

    public function getSpecifications(Server $server)
    {
        return;
    }

    public function updateSpecifications(Server $server, UpdateDetailsRequest $request)
    {

    }
}
