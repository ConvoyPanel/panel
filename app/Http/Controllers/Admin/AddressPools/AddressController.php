<?php

namespace Convoy\Http\Controllers\Admin\AddressPools;

use Convoy\Enums\Network\AddressType;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Http\Controllers\ApiController;
use Convoy\Http\Requests\Admin\AddressPools\Addresses\StoreAddressRequest;
use Convoy\Http\Requests\Admin\AddressPools\Addresses\UpdateAddressRequest;
use Convoy\Jobs\Server\SyncNetworkSettings;
use Convoy\Models\Address;
use Convoy\Models\AddressPool;
use Convoy\Models\Filters\FiltersAddress;
use Convoy\Repositories\Eloquent\AddressRepository;
use Convoy\Services\Servers\NetworkService;
use Convoy\Transformers\Admin\AddressTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class AddressController extends ApiController
{
    public function __construct(
        private NetworkService $networkService, private AddressRepository $repository,
        private ConnectionInterface $connection,
    )
    {
    }

    public function index(Request $request, AddressPool $addressPool)
    {
        $addresses = QueryBuilder::for($addressPool->addresses())
                                 ->with('server')
                                 ->defaultSort('-id')
                                 ->allowedFilters(
                                     [AllowedFilter::exact('address'), AllowedFilter::exact(
                                         'type',
                                     ), AllowedFilter::custom(
                                         '*',
                                         new FiltersAddress(),
                                     ), AllowedFilter::exact('server_id')->nullable()],
                                 )
                                 ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($addresses, new AddressTransformer())->parseIncludes($request->include)
                                                            ->respond();
    }

    public function store(StoreAddressRequest $request, AddressPool $addressPool)
    {
        if (!$request->boolean('is_bulk_action')) {
            /** @var Address $address */
            $address = $this->connection->transaction(function () use ($request, $addressPool) {
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
            });

            return fractal($address, new AddressTransformer())->parseIncludes($request->include)
                                                              ->respond();
        }

        if ($request->boolean('is_bulk_action')) {
            $this->connection->transaction(function () use ($request, $addressPool) {
                if ($request->enum('type', AddressType::class) === AddressType::IPV4) {
                    $this->repository->bulkCreateIPv4Addresses(
                        $request->starting_address,
                        $request->ending_address,
                        $addressPool->id,
                        $request->server_id,
                        $request->cidr,
                        $request->gateway,
                        $request->mac_address,
                    );
                } else {
                    $this->repository->bulkCreateIPv6Addresses(
                        $request->starting_address,
                        $request->ending_address,
                        $addressPool->id,
                        $request->server_id,
                        $request->cidr,
                        $request->gateway,
                        $request->mac_address,
                    );
                }
            });

            if (!is_null($request->server_id)) {
                SyncNetworkSettings::dispatch($request->integer('server_id'));
            }

            return $this->returnNoContent();
        }
    }

    public function update(
        UpdateAddressRequest $request, AddressPool $addressPool, Address $address,
    )
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

        return fractal($address, new AddressTransformer())->parseIncludes($request->include)
                                                          ->respond();
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
