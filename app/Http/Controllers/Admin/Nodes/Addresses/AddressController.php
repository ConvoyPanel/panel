<?php

namespace Convoy\Http\Controllers\Admin\Nodes\Addresses;

use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use Convoy\Http\Requests\Admin\Nodes\Addresses\UpdateAddressRequest;
use Convoy\Models\IPAddress;
use Convoy\Models\Node;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends ApplicationApiController
{
    public function __construct(private NetworkService $service)
    {
    }

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

        if ($request->show_available_ips) {
            $builder = $builder->where('server_id', null);
        }

        return $builder->paginate($request->query('per_page') ?? 50);
    }
}
