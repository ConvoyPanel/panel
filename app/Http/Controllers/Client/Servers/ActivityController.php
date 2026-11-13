<?php

namespace Convoy\Http\Controllers\Client\Servers;

use Convoy\Models\Filters\SearchTerm;
use Convoy\Models\Server;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Http\Controllers\Controller;
use Convoy\Transformers\Client\ActivityLogTransformer;

class ActivityController extends Controller
{
    public function __invoke(Server $server, Request $request)
    {
        $activity = QueryBuilder::for($server->activity())
            ->with('actor')
            // MySQL handed rows back in id order without being asked; Postgres
            // doesn't, and pages would overlap. Qualified: the pivot has an id too.
            ->defaultSort('activity_logs.id')
            ->allowedSorts(['created_at', 'updated_at'])
            ->allowedFilters([
                'event',
                // A partial filter runs LOWER(batch) LIKE ?, and batch is a native
                // uuid column on Postgres, which LOWER() doesn't take.
                AllowedFilter::callback('batch', fn (Builder $query, $value) => $query->whereRaw(
                    'CAST(activity_logs.batch AS TEXT) LIKE ?',
                    [SearchTerm::contains($value)],
                )),
                'status',
            ])
            ->paginate(min($request->query('per_page', 25), 100))
            ->appends($request->query());

        return fractal($activity, new ActivityLogTransformer())->respond();
    }
}
