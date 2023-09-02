<?php

namespace Convoy\Http\Controllers\Admin\AddressPools;

use Convoy\Models\Address;
use Illuminate\Support\Facades\Log;
use Convoy\Enums\Network\AddressType;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Database\ConnectionInterface;
use Convoy\Transformers\Admin\AddressTransformer;
use Convoy\Services\Nodes\Addresses\AddressHelpers;
use Convoy\Http\Controllers\ApplicationApiController;
use Convoy\Models\AddressPool;
use Convoy\Models\Filters\AllowedNullableFilter;
use Convoy\Models\Filters\FiltersAddress;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Http\Requests\Admin\AddressPools\Addresses\StoreAddressRequest;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Convoy\Http\Requests\Admin\AddressPools\Addresses\UpdateAddressRequest;

class AddressController extends ApplicationApiController
{
    public function __construct(private NetworkService $networkService, private AddressHelpers $addressHelpers, private ConnectionInterface $connection)
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
        /** @var Address|Address[] $address */
        $address = $this->connection->transaction(function () use ($request, $addressPool) {
            if ($request->boolean('is_bulk_action')) {
                $addressesToAdd = $this->addressHelpers->expandIpRange(
                    $request->enum('type', AddressType::class),
                    $request->starting_address,
                    $request->ending_address,
                );

                foreach ($addressesToAdd as &$address) {
                    $address = [
                        'address' => $address,
                        'address_pool_id' => $addressPool->id,
                        ...$request->safe()->only(['server_id', 'type', 'cidr', 'gateway', 'mac_address'])
                    ];
                }

                


            } else {
                $address = $addressPool->addresses()->create($request->validated());

                if ($request->server_id) {
                    try {
                        $this->networkService->syncSettings($address->server);
                    } catch (ProxmoxConnectionException) {
                        throw new ServiceUnavailableHttpException(
                            message: "Server {$address->server->uuid} failed to sync network settings.",
                        );
                    }
                }

                return $address;
            }
        });

        return fractal($address, new AddressTransformer)->parseIncludes($request->include)->respond();
    }

    public function update(UpdateAddressRequest $request, AddressPool $addressPool, Address $address)
    {
        $address = $this->connection->transaction(function () use ($request, $address) {
            $oldLinkedServer = $address->server;

            $address->update($request->validated());

            try {
                // Detach old server
                if ($oldLinkedServer) {
                    $this->networkService->syncSettings($oldLinkedServer);
                }

                // Attach new server
                if ($address->server) {
                    $this->networkService->syncSettings($address->server);
                }
            } catch (ProxmoxConnectionException) {
                if ($oldLinkedServer && !$address->server) {
                    throw new ServiceUnavailableHttpException(
                        message: "Server {$oldLinkedServer->uuid} failed to sync network settings.",
                    );
                } elseif (!$oldLinkedServer && $address->server) {
                    throw new ServiceUnavailableHttpException(
                        message: "Server {$address->server->uuid} failed to sync network settings.",
                    );
                } elseif ($oldLinkedServer && $address->server) {
                    throw new ServiceUnavailableHttpException(
                        message: "Servers {$oldLinkedServer->uuid} and {$address->server->uuid} failed to sync network settings.",
                    );
                }
            }

            return $address;
        });

        return fractal($address, new AddressTransformer)->parseIncludes($request->include)->respond();
    }

    public function destroy(AddressPool $addressPool, Address $address)
    {
        $this->connection->transaction(function () use ($address) {
            $address->delete();

            if ($address->server) {
                try {
                    $this->networkService->syncSettings($address->server);
                } catch (ProxmoxConnectionException) {
                    throw new ServiceUnavailableHttpException(
                        message: "Server {$address->server->uuid} failed to sync network settings.",
                    );
                }
            }
        });

        return $this->returnNoContent();
    }
}
