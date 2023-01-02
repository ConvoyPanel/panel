<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Servers\StoreServerRequest;
use Convoy\Models\Filters\FiltersServer;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerCreationService;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\ServerTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends Controller
{
    public function __construct(private ServerDetailService $detailService, private ServerCreationService $creationService)
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

    public function store(StoreServerRequest $request)
    {
        $server = $this->creationService->handle($request->validated());

        $server->load(['addresses', 'user', 'node']);

        return fractal($server, new ServerTransformer())->parseIncludes(['user', 'node'])->respond();
    }

    public function suspend()
    {
    }

    public function unsuspend()
    {
    }
}
