<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use Convoy\Http\Requests\Admin\Nodes\Addresses\UpdateAddressRequest;
use Convoy\Models\IPAddress;
use Convoy\Models\Node;
use Convoy\Services\Servers\NetworkService;
use Convoy\Transformers\Admin\AddressTransformer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends Controller
{
    public function __construct(private NetworkService $networkService)
    {
    }

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
        $address = IPAddress::create(array_merge(['node_id' => $node->id], $request->safe()->except('sync_network_config')))->load('server');

        try {
            if ($request->sync_network_config && $request->server_id) {
                $this->networkService->syncSettings($address->server);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return fractal($address, new AddressTransformer)->parseIncludes('server')->respond();
    }

    public function update(UpdateAddressRequest $request, Node $node, IPAddress $address)
    {
        $oldServer = $address->server;

        $address->update($request->safe()->except('sync_network_config'));

        $newServer = $address->server;

        try {
            if ($request->sync_network_config && $oldServer?->id !== $newServer?->id) {
                if ($oldServer)
                    $this->networkService->syncSettings($oldServer);

                if ($newServer)
                    $this->networkService->syncSettings($newServer);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return fractal($address, new AddressTransformer)->parseIncludes('server')->respond();
    }

    public function destroy(Request $request, Node $node, IPAddress $address)
    {
        $server = $address->server;

        $address->delete();

        try {
            if ($request->sync_network_config && $server) {
                $this->networkService->syncSettings($server);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return response()->noContent();
    }
}
