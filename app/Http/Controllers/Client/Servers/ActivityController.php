<?php

namespace App\Http\Controllers\Client\Servers;

use App\Data\Activity\ActivityLogData;
use App\Data\PaginationMeta;
use App\Models\Server;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ActivityController
{
    public function __invoke(Server $server, Request $request)
    {
        $activity = QueryBuilder::for($server->activity())
            ->with('actor')
            ->allowedSorts(['created_at', 'updated_at'])
            ->allowedFilters(['event', 'batch', 'status'])
            ->paginate(min($request->query('per_page', 25), 100))
            ->appends($request->query());

        return PaginationMeta::paginate($activity, ActivityLogData::class);
    }
}
