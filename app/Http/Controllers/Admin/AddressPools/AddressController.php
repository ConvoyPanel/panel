<?php

namespace App\Http\Controllers\Admin\AddressPools;

use App\Enums\Network\AddressType;
use App\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Admin\AddressPools\Addresses\StoreAddressRequest;
use App\Http\Requests\Admin\AddressPools\Addresses\UpdateAddressRequest;
use App\Jobs\Server\SyncNetworkSettings;
use App\Models\Address;
use App\Models\AddressPool;
use App\Models\Filters\FiltersAddressWildcard;
use App\Services\Activity\BulkAddressCreationService;
use App\Services\Servers\NetworkService;
use App\Transformers\Admin\AddressTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class AddressController extends ApiController
{
    public function __construct(
        private NetworkService $networkService,
        private ConnectionInterface $connection,
        private BulkAddressCreationService $bulkAddressCreationService,
    ) {
    }

    public function index(Request $request, AddressPool $addressPool)
    {
        $addresses = QueryBuilder::for($addressPool->addresses())
                                 ->with('server')
                                 ->defaultSort('-id')
                                 ->allowedFilters(
                                     [
                                         AllowedFilter::exact('address'),
                                         AllowedFilter::exact(
                                             'type',
                                         ),
                                         AllowedFilter::custom(
                                             '*',
                                             new FiltersAddressWildcard(),
                                         ),
                                         AllowedFilter::exact('server_id')->nullable(),
                                     ],
                                 )
                                 ->paginate(min($request->query('per_page', 50), 100))->appends(
                                     $request->query(),
                                 );

        return fractal($addresses, new AddressTransformer())->parseIncludes($request->include)
                                                            ->respond();
    }

    public function store(StoreAddressRequest $request, AddressPool $addressPool)
    {
        $data = $request->validated();

        if ($data['is_bulk_action']) {
            $this->bulkAddressCreationService->handle(
                type      : AddressType::from($data['type']),
                from      : $data['starting_address'],
                to        : $data['ending_address'],
                poolId    : $addressPool->id,
                serverId  : $data['server_id'],
                cidr      : $data['cidr'],
                gateway   : $data['gateway'],
                macAddress: $data['mac_address'],
            );

            if (! is_null($request->server_id)) {
                SyncNetworkSettings::dispatch($request->integer('server_id'));
            }

            return $this->returnNoContent();
        }

        /** @var Address $address */
        $address = $this->connection->transaction(function () use ($data, $addressPool) {
            $address = $addressPool->addresses()->create([
                ...$data,
                'address_pool_id' => $addressPool->id,
            ]);

            if ($data['server_id']) {
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

    public function update(
        UpdateAddressRequest $request,
        AddressPool $addressPool,
        Address $address,
    ) {
        $address = $this->connection->transaction(function () use ($request, $address) {
            $oldLinkedServer = $address->server;

            $address->update($request->validated());

            $address->load('server'); // update the server relationship

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
                if ($oldLinkedServer && ! $address->server) {
                    throw new ServiceUnavailableHttpException(
                        message: "Server {$oldLinkedServer->uuid} failed to sync network settings.",
                    );
                } elseif (! $oldLinkedServer && $address->server) {
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
