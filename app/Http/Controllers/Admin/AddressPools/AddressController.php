<?php

namespace Convoy\Http\Controllers\Admin\AddressPools;

use Illuminate\Support\Facades\Log;
use Convoy\Services\Servers\NetworkService;
use Convoy\Transformers\Admin\AddressTransformer;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\AddressPool;
use Convoy\Models\Filters\AllowedNullableFilter;
use Convoy\Models\Filters\FiltersAddress;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Http\Requests\Admin\AddressPools\Addresses\StoreAddressRequest;

class AddressController extends ApplicationApiController
{
    public function __construct(private NetworkService $networkService)
    {
    }

    public function index(Request $request, AddressPool $addressPool)
    {
        $addresses = QueryBuilder::for($addressPool->addresses())
            ->with('server')
            ->defaultSort('-id')
            ->allowedFilters(
                ['address', AllowedFilter::exact('type'), AllowedFilter::custom(
                    '*',
                    new FiltersAddress,
                ), AllowedNullableFilter::exact('server_id')],
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends($request->query());

        return fractal($addresses, new AddressTransformer)->parseIncludes($request->include)->respond();
    }

    public function store(StoreAddressRequest $request, AddressPool $addressPool)
    {
        $address = $addressPool->addresses()->create($request->validated());

        if ($request->server_id) {
            try {
                $this->networkService->syncSettings($address->server);
            } catch (ProxmoxConnectionException) {
                Log::error(
                    "Server {uuid} failed to sync network settings to include address {id}",
                    ['uuid' => $address->server->uuid, 'id' => $address->id],
                );
            }
        }

        return fractal($address, new AddressTransformer)->parseIncludes($request->include)->respond();
    }
}
