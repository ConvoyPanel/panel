<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\Controller;
use Convoy\Models\Server;
use Convoy\Services\Servers\ServerDetailService;
use Convoy\Transformers\Client\ServerTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ServerController extends Controller
{
    public function __construct(private ServerDetailService $detailService)
    {
    }

    public function index(Request $request)
    {
        $servers = QueryBuilder::for(Server::query())
            ->with('addresses')
            ->allowedFilters([AllowedFilter::exact('node_id'), AllowedFilter::exact('user_id'), 'name',])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($servers->through(fn($server) => $this->detailService->getByEloquent($server)), new ServerTransformer())->respond();
    }
}
