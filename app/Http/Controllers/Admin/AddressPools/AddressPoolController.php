<?php

namespace App\Http\Controllers\Admin\AddressPools;

use App\Http\Requests\Admin\AddressPools\StoreAddressPoolRequest;
use App\Http\Requests\Admin\AddressPools\UpdateAddressPoolRequest;
use App\Models\AddressBlockGroup;
use App\Models\Filters\FiltersAddressPoolWildcard;
use App\Models\Filters\FiltersNodeWildcard;
use App\Transformers\Admin\AddressBlockGroupTransformer;
use App\Transformers\Admin\NodeTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AddressPoolController
{
    public function index(Request $request)
    {
        $addressPools = QueryBuilder::for(AddressBlockGroup::query())
            ->withCount(['addresses', 'nodes'])
            ->defaultSort('-id')
            ->allowedFilters(
                ['name', AllowedFilter::custom(
                    '*',
                    new FiltersAddressPoolWildcard,
                )],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($addressPools, new AddressBlockGroupTransformer)->respond();
    }

    public function show(AddressBlockGroup $addressPool)
    {
        $addressPool->loadCount(['addresses', 'nodes']);

        return fractal($addressPool, new AddressBlockGroupTransformer)->respond();
    }

    public function getAttachedNodes(Request $request, AddressBlockGroup $addressPool)
    {
        $nodes = QueryBuilder::for($addressPool->nodes())
            ->withCount('servers')
            ->allowedFilters(
                ['name', 'fqdn', AllowedFilter::exact(
                    'location_id',
                ), AllowedFilter::custom('*', new FiltersNodeWildcard)],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($nodes, new NodeTransformer)->respond();
    }

    public function store(StoreAddressPoolRequest $request)
    {
        $pool = AddressBlockGroup::create($request->safe()->except('node_ids'));
        $pool->nodes()->attach($request->node_ids);
        $pool->loadCount(['addresses', 'nodes']);

        return fractal($pool, new AddressBlockGroupTransformer)->respond();
    }

    public function update(UpdateAddressPoolRequest $request, AddressBlockGroup $addressPool)
    {
        $addressPool->update($request->safe()->except('node_ids'));
        $addressPool->nodes()->sync($request->node_ids);
        $addressPool->loadCount(['addresses', 'nodes']);

        return fractal($addressPool, new AddressBlockGroupTransformer)->respond();
    }

    public function destroy(AddressBlockGroup $addressPool)
    {
        $addressPool->loadCount('nodes');

        if ($addressPool->nodes_count > 0) {
            throw new AccessDeniedHttpException(
                'This address pool cannot be deleted while still allocated to nodes.',
            );
        }

        $addressPool->delete();

        return response()->noContent();
    }
}
