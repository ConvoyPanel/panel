<?php

namespace App\Http\Controllers\Admin\Nodes\Addresses;

use App\Enums\Network\AddressType;
use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use App\Http\Requests\Admin\Nodes\Addresses\UpdateAddressRequest;
use App\Models\IPAddress;
use App\Models\Node;
use App\Models\Server;
use App\Services\Servers\NetworkService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedInclude;
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
        if ($request->server_id)
        {
            $this->service->validateForDuplicates($request->server_id, $request->type);
        }

        if (AddressType::from($request->type) === AddressType::IPV4)
        {
            IPAddress::create($request->validated());
        } else {
            IPAddress::create($request->safe()->except(['mac_address']));
        }

        return redirect()->route('admin.nodes.show.addresses', [$node->id]);
    }

    public function update(Node $node, IPAddress $address, UpdateAddressRequest $request)
    {
        if ($request->server_id)
        {
            $this->service->validateForDuplicates($request->server_id, $request->type, $address->id);
        }

        if (AddressType::from($request->type) === AddressType::IPV4)
        {
            $address->update($request->validated());
        } else {
            $address->update($request->safe()->except(['mac_address']));
        }

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
            $builder = $builder->where('server_id', null);
        }

        return $builder->paginate($request->query('per_page') ?? 50);
    }
}
