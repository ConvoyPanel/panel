<?php

namespace App\Http\Controllers\Application\Nodes\Addresses;

use App\Enums\Network\AddressType;
use App\Http\Controllers\ApplicationApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Application\Nodes\Addresses\StoreAddressRequest;
use App\Http\Requests\Application\Nodes\Addresses\UpdateAddressRequest;
use App\Models\IPAddress;
use App\Models\Node;
use App\Services\Servers\NetworkService;
use App\Transformers\Application\AddressTransformer;
use Illuminate\Http\Request;
use PharIo\Manifest\Application;
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

        return fractal($address, new AddressTransformer())->respond();
    }

    public function store(Node $node, StoreAddressRequest $request)
    {
        if ($request->server_id)
        {
            $this->service->validateForDuplicates($request->server_id, $request->type);
        }

        $address = null;

        if (AddressType::from($request->type) === AddressType::IPV4)
        {
            $address = IPAddress::create($request->validated());
        } else {
            $address = IPAddress::create($request->safe()->except(['mac_address']));
        }


        return fractal($address, new AddressTransformer())->respond();
    }

    public function destroy(Node $node, IPAddress $address)
    {
        $address->delete();

        return $this->returnNoContent();
    }
}
