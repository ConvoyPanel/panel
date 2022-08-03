<?php

namespace App\Http\Controllers\Application\Nodes\Addresses;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\Nodes\Addresses\StoreAddressRequest;
use App\Http\Requests\Application\Nodes\Addresses\UpdateAddressRequest;
use App\Models\IPAddress;
use App\Models\Node;
use App\Transformers\Application\AddressTransformer;
use Illuminate\Http\Request;
use PharIo\Manifest\Application;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends ApplicationApiController
{
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
        $address = $address->update($request->validated());

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
