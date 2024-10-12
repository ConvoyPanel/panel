<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Admin\LocationFormRequest;
use Convoy\Models\Filters\FiltersLocationWildcard;
use Convoy\Models\Location;
use Convoy\Transformers\Admin\LocationTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LocationController extends ApiController
{
    public function index(Request $request)
    {
        $locations = QueryBuilder::for(Location::query())
                                 ->withCount(['nodes', 'servers'])
                                 ->defaultSort('-id')
            // @phpstan-ignore-next-line
                                 ->allowedFilters(
                                     ['short_code', AllowedFilter::custom('*', new FiltersLocationWildcard())],
                                 )
                                 ->paginate(min($request->query('per_page', 50), 100))->appends(
                                     $request->query(),
                                 );

        return fractal($locations, new LocationTransformer())->respond();
    }

    public function store(LocationFormRequest $request)
    {
        $location = Location::create($request->validated());

        return fractal($location, new LocationTransformer())->respond();
    }

    public function update(LocationFormRequest $request, Location $location)
    {
        $location->update($request->validated());

        return fractal($location, new LocationTransformer())->respond();
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

        return $this->returnNoContent();
    }
}
