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
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        if ($request->server_id)
        {
            $existingAddress = Server::find($request->server_id)->addresses()->where('type', AddressType::from($request->type)->value)->first();

            if ($existingAddress !== null && $existingAddress->server_id !== $request->server_id)
            {
                throw ValidationException::withMessages([
                    'server_id' => "This server already has an {$request->type} address."
                ]);
            }
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
            $existingAddress = Server::find($request->server_id)->addresses()->where('type', AddressType::from($request->type)->value)->first();

            if ($existingAddress !== null && $existingAddress->server_id === $request->server_id && $existingAddress->id !== $address->id)
            {
                throw ValidationException::withMessages([
                    'server_id' => "This server already has an {$request->type} address."
                ]);
            }
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
            return $builder->where('server_id', null)->get();
        }

        return $builder->get();
    }
}
