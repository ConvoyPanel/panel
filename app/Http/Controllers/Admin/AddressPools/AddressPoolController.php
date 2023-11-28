<?php

namespace Convoy\Http\Controllers\Admin\AddressPools;

use Illuminate\Http\Request;
use Convoy\Models\AddressPool;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Models\Filters\FiltersNode;
use Spatie\QueryBuilder\AllowedFilter;
use Convoy\Models\Filters\FiltersAddressPool;
use Convoy\Transformers\Admin\NodeTransformer;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Transformers\Admin\AddressPoolTransformer;
use Convoy\Http\Requests\Admin\AddressPools\StoreAddressPoolRequest;
use Convoy\Http\Requests\Admin\AddressPools\UpdateAddressPoolRequest;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AddressPoolController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $addressPools = QueryBuilder::for(AddressPool::query())
            ->withCount(['addresses', 'nodes'])
            ->defaultSort('-id')
            ->allowedFilters(['name', AllowedFilter::custom('*', new FiltersAddressPool)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($addressPools, new AddressPoolTransformer)->respond();
    }

    public function show(AddressPool $addressPool)
    {
        $addressPool->loadCount(['addresses', 'nodes']);

        return fractal($addressPool, new AddressPoolTransformer)->respond();
    }

    public function getAttachedNodes(Request $request, AddressPool $addressPool)
    {
        $nodes = QueryBuilder::for($addressPool->nodes())
            ->withCount('servers')
            ->allowedFilters(['name', 'fqdn', AllowedFilter::exact('location_id'), AllowedFilter::custom('*', new FiltersNode)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($nodes, new NodeTransformer())->respond();
    }

    public function store(StoreAddressPoolRequest $request)
    {
        $pool = AddressPool::create($request->safe()->except('node_ids'));
        $pool->nodes()->attach($request->node_ids);
        $pool->loadCount(['addresses', 'nodes']);

        return fractal($pool, new AddressPoolTransformer)->respond();
    }

    public function update(UpdateAddressPoolRequest $request, AddressPool $addressPool)
    {
        $addressPool->update($request->safe()->except('node_ids'));
        $addressPool->nodes()->sync($request->node_ids);
        $addressPool->loadCount(['addresses', 'nodes']);

        return fractal($addressPool, new AddressPoolTransformer)->respond();
    }

    public function destroy(AddressPool $addressPool)
    {
        $addressPool->loadCount('nodes');

        if ($addressPool->nodes_count > 0) {
            throw new AccessDeniedHttpException('This address pool cannot be deleted while still allocated to nodes.');
        }

        $addressPool->delete();

        return $this->returnNoContent();
    }
}
