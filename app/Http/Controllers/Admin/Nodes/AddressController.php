<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use Convoy\Http\Requests\Admin\Nodes\Addresses\UpdateAddressRequest;
use Convoy\Models\IPAddress;
use Convoy\Models\Node;
use Convoy\Transformers\Admin\AddressTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends Controller
{
    public function index(Request $request, Node $node)
    {
        $addresses = QueryBuilder::for(IPAddress::query())
            ->with('server')
            ->where('ip_addresses.node_id', $node->id)
            ->allowedFilters(['address', AllowedFilter::exact('type')])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($addresses, new AddressTransformer)->parseIncludes($request->includes)->respond();
    }

    public function store(StoreAddressRequest $request, Node $node)
    {
        $address = IPAddress::create(array_merge(['node_id' => $node->id], $request->safe()->except('sync_server_config')))->load('server');

        if ($request->sync_server_config) {

        }

        return fractal($address, new AddressTransformer)->respond();
    }

    public function update(UpdateAddressRequest $request, Node $node, IPAddress $address)
    {
        $address->update($request->safe()->except('sync_server_config'));

        return fractal($address, new AddressTransformer)->respond();
    }

    public function destroy(Request $request, Node $node, IPAddress $address)
    {
        $address->delete();

        return response()->noContent();
    }
}
