<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\LocationFormRequest;
use Convoy\Models\Location;
use Convoy\Transformers\Admin\LocationTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LocationController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $locations = QueryBuilder::for(Location::query())
            ->withCount(['nodes', 'servers'])
            ->allowedFilters(['short_code'])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

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

        return $this->returnNoContent();
    }

    public function destroy(Location $location)
    {
        $location->loadCount('nodes');

        if ($location->nodes_count > 0) {
            throw new BadRequestHttpException('The location cannot be deleted with nodes still associated.');
        }

        $location->delete();

        return $this->returnNoContent();
    }
}
