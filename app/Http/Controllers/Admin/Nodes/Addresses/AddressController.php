<?php

namespace App\Http\Controllers\Admin\Nodes\Addresses;

use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use App\Http\Requests\Admin\Nodes\Addresses\UpdateAddressRequest;
use App\Models\IPAddress;
use App\Models\Node;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends ApplicationApiController
{
    public function index(Node $node)
    {
        return Inertia::render('admin/nodes/addresses/Index', [
            'node' => $node,
            'addresses' => $node->addresses,
        ]);
    }

    public function store(Node $node, StoreAddressRequest $request)
    {
        IPAddress::create($request->validated());

        return redirect()->route('admin.nodes.show.addresses', [$node->id]);
    }

    public function update(Node $node, IPAddress $address, UpdateAddressRequest $request)
    {
        $address->update($request->validated());

        return redirect()->route('admin.nodes.show.addresses', [$node->id]);
    }

    public function destroy(Node $node, IPAddress $address)
    {
        $address->delete();

        return redirect()->route('admin.nodes.show.addresses', [$node->id]);
    }

    public function search(Node $node, Request $request)
    {
        $builder = QueryBuilder::for(IPAddress::class)
            ->allowedFilters(['server_id', 'address', 'cidr', 'gateway', 'type'])->where('node_id', $node->id);

        if ($request->show_available_ips)
        {
            return $builder->where('server_id', null)->get();
        }

        return $builder->get();
    }
}
