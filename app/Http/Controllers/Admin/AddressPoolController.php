<?php

namespace Convoy\Http\Controllers\Admin;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\AddressPools\StoreAddressPoolRequest;
use Convoy\Http\Requests\Admin\AddressPools\UpdateAddressPoolRequest;
use Convoy\Models\AddressPool;
use Convoy\Models\Filters\FiltersAddressPool;
use Convoy\Models\Filters\FiltersNode;
use Convoy\Transformers\Admin\AddressPoolTransformer;
use Convoy\Transformers\Admin\NodeTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AddressPoolController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $addressPools = QueryBuilder::for(AddressPool::query())
            ->withCount(['addresses', 'nodes'])
            ->allowedFilters(['name', AllowedFilter::custom('*', new FiltersAddressPool)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($addressPools, new AddressPoolTransformer)->respond();
    }

    public function show(AddressPool $addressPool)
    {
        $addressPool->loadCount(['addresses', 'nodes']);

        return fractal($addressPool, new AddressPoolTransformer)->respond();
    }

    public function getNodesAllocatedTo(Request $request, AddressPool $addressPool)
    {
        $nodes = QueryBuilder::for($addressPool->nodes())
            ->withCount('servers')
            ->allowedFilters(['name', 'fqdn', AllowedFilter::exact('location_id'), AllowedFilter::custom('*', new FiltersNode)])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($nodes, new NodeTransformer())->respond();
    }

    public function store(StoreAddressPoolRequest $request)
    {
        $pool = AddressPool::create($request->validated());
        $pool->loadCount(['addresses', 'nodes']);

        return fractal($pool, new AddressPoolTransformer)->respond();
    }

    public function update(UpdateAddressPoolRequest $request, AddressPool $addressPool)
    {
        $addressPool->update($request->validated());
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
