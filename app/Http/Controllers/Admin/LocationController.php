<?php

namespace App\Http\Controllers\Admin;

use App\Data\Location\LocationData;
use App\Data\Node\NodeData;
use App\Data\PaginationMeta;
use App\Enums\Audit\AuditEvent;
use App\Facades\Audit;
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

        Audit::record(
            AuditEvent::ADMIN_LOCATION_CREATED,
            subject: $location,
            properties: ['short_code' => $location->short_code],
        );

        return LocationData::from($location);
    }

    public function update(LocationFormRequest $request, Location $location)
    {
        $location->update($request->validated());

        Audit::record(
            AuditEvent::ADMIN_LOCATION_UPDATED,
            subject: $location,
            properties: ['short_code' => $location->short_code, 'changed' => array_keys($location->getChanges())],
        );

        $location->loadCount('nodes', 'servers');

        return LocationData::from($location);
    }

    public function destroy(Location $location)
    {
        $location->loadCount('nodes');

        if ($location->nodes_count > 0) {
            throw new BadRequestHttpException(
                'The location cannot be deleted with nodes still associated.',
            );
        }

        $shortCode = $location->short_code;

        $location->delete();

        Audit::record(
            AuditEvent::ADMIN_LOCATION_DELETED,
            subject: $location,
            properties: ['short_code' => $shortCode],
        );

        return response()->noContent();
    }
}
