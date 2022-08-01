<?php

namespace App\Http\Controllers\Application\Nodes\Addresses;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\Nodes\Addresses\StoreAddressRequest;
use App\Http\Requests\Application\Nodes\Addresses\UpdateAddressRequest;
use App\Models\IPAddress;
use App\Models\Node;
use Illuminate\Http\Request;
use PharIo\Manifest\Application;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends ApplicationApiController
{
    public function index(Request $request)
    {
        $addresses = QueryBuilder::for(IPAddress::query())
            ->allowedFilters(['server_id', 'node_id', 'address', 'cidr', 'gateway', 'type'])
            ->allowedSorts(['id', 'server_id', 'node_id'])
            ->paginate($request->query('per_page') ?? 50);

        return $addresses;
    }

    public function show(IPAddress $address)
    {
        return $this->returnContent([
            'data' => $address,
        ]);
    }

    public function update(Node $node, IPAddress $address, UpdateAddressRequest $request)
    {
        $address = $address->update($request->validated());

        return $this->returnContent([
            'message' => 'Updated address',
            'data' => $address,
        ]);
    }

    public function store(Node $node, StoreAddressRequest $request)
    {
        $address = IPAddress::create($request->validated());

        return $this->returnContent([
            'message' => 'Created address',
            'data' => $address,
        ]);
    }

    public function destroy(Node $node, IPAddress $address)
    {
        $address->delete();

        return $this->returnContent([
            'message' => 'Deleted address',
        ]);
    }
}
