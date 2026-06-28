<?php

namespace App\Http\Controllers\Admin;

use App\Data\Location\LocationData;
use App\Data\Node\NodeData;
use App\Data\PaginationMeta;
use App\Http\Requests\Admin\LocationFormRequest;
use App\Models\Filters\FiltersLocationWildcard;
use App\Models\Location;
use Illuminate\Http\Request;
use Spatie\LaravelData\DataCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LocationController
{
    public function index(Request $request)
    {
        $locations = QueryBuilder::for(Location::query())
            ->withCount(['nodes', 'servers'])
            ->defaultSort('short_code')
            // @phpstan-ignore-next-line
            ->allowedFilters(
                ['short_code', AllowedFilter::custom('*', new FiltersLocationWildcard)],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($locations, LocationData::class);
    }

    public function show(Location $location)
    {
        $location->loadCount('nodes', 'servers');

        return LocationData::from($location);
    }

    public function showAttachedNodes(Location $location)
    {
        $nodes = $location->nodes()
            ->withCount('servers')
            ->orderBy('name')
            ->get();

        return NodeData::collect($nodes, DataCollection::class);
    }

    public function store(LocationFormRequest $request)
    {
        $location = Location::create($request->validated());
        $location->loadCount('nodes', 'servers');

        return LocationData::from($location);
    }

    public function update(LocationFormRequest $request, Location $location)
    {
        $location->update($request->validated());
        $location->loadCount('nodes', 'servers');

        return LocationData::from($location);
    }

    public function destroy(Location $location)
    {
        $location->loadCount('nodes');

        // @phpstan-ignore-next-line
        if ($location->nodes_count > 0) {
            throw new BadRequestHttpException(
                'The location cannot be deleted with nodes still associated.',
            );
        }

        $location->delete();

        return response()->noContent();
    }
}
