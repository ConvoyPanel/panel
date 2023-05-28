<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Http\Controllers\Controller;
use Convoy\Http\Requests\Admin\Nodes\Addresses\StoreAddressRequest;
use Convoy\Http\Requests\Admin\Nodes\Addresses\UpdateAddressRequest;
use Convoy\Models\Filters\AllowedNullableFilter;
use Convoy\Models\Filters\FiltersAddress;
use Convoy\Models\Address;
use Convoy\Models\Node;
use Convoy\Services\Servers\NetworkService;
use Convoy\Transformers\Admin\AddressTransformer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController extends Controller
{
    public function __construct(private NetworkService $networkService)
    {
    }

    public function index(Request $request, Node $node)
    {
        $addresses = QueryBuilder::for(Address::query())
            ->with('server')
            ->where('ip_addresses.node_id', $node->id)
            ->allowedFilters(['address', AllowedFilter::exact('type'), AllowedFilter::custom('*', new FiltersAddress), AllowedNullableFilter::exact('server_id')])
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($addresses, new AddressTransformer)->parseIncludes($request->includes)->respond();
    }

    public function store(StoreAddressRequest $request, Node $node)
    {
        $address = Address::create(array_merge(['node_id' => $node->id], $request->validated()));

        $address->load('server');

        try {
            if ($request->server_id) {
                $this->networkService->syncSettings($address->server);
            }
        } catch (Exception $e) {
            // do nothing
        }

        return fractal($address, new AddressTransformer)->parseIncludes(['server'])->respond();
    }

    public function update(UpdateAddressRequest $request, Node $node, Address $address)
    {
        $address->load('server');

        $oldServer = $address->server;

        $address->update($request->validated());

        $address->refresh();

        $newServer = $address->server;

        try {
            if ($oldServer?->id !== $newServer?->id) {
                if ($oldServer) {
                    $this->networkService->syncSettings($oldServer);
                }

                if ($newServer) {
                    $this->networkService->syncSettings($newServer);
                }
            }
        } catch (Exception $e) {
            // do nothing
        }

        $address->load('server');

        return fractal($address, new AddressTransformer)->parseIncludes(['server'])->respond();
    }

    public function destroy(Node $node, Address $address): Response
    {
        $address->load('server');

        $server = $address->server;

        $address->delete();

        try {
            if ($server) {
                $this->networkService->syncSettings($server);
            }
        } catch (Exception $e) {
            // do nothing
        }

        return response()->noContent();
    }
}
