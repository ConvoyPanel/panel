<?php

namespace Convoy\Http\Controllers\Application\Nodes\Addresses;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Application\Nodes\Addresses\StoreAddressRequest;
use Convoy\Http\Requests\Application\Nodes\Addresses\UpdateAddressRequest;
use Convoy\Models\IPAddress;
use Convoy\Models\Node;
use Convoy\Services\Servers\NetworkService;
use Convoy\Transformers\Application\AddressTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends ApplicationApiController
{
    public function __construct(private NetworkService $service)
    {
    }

    public function index(Node $node, Request $request)
    {
        $addresses = QueryBuilder::for(IPAddress::query())
            ->allowedFilters(['server_id', 'node_id', 'address', 'cidr', 'gateway', 'type'])
            ->allowedSorts(['id', 'server_id', 'node_id'])
            ->where('node_id', $node->id)
            ->paginate($request->query('per_page') ?? 50);

        return fractal($addresses, new AddressTransformer())->respond();
    }

    public function show(Node $node, IPAddress $address)
    {
        return fractal($address, new AddressTransformer())->respond();
    }

    public function update(Node $node, IPAddress $address, UpdateAddressRequest $request)
    {
        $address->update($request->validated());

        return fractal($address, new AddressTransformer())->respond();
    }

    public function store(Node $node, StoreAddressRequest $request)
    {
        $address = IPAddress::create($request->validated());

        return fractal($address, new AddressTransformer())->respond();
    }

    public function destroy(Node $node, IPAddress $address)
    {
        $address->delete();

        return $this->returnNoContent();
    }
}
