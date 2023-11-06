<?php

namespace Convoy\Http\Controllers\Admin\Nodes;

use Convoy\Models\Node;
use Convoy\Models\Address;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Convoy\Http\Controllers\Controller;
use Convoy\Models\Filters\FiltersAddress;
use Convoy\Services\Servers\NetworkService;
use Illuminate\Database\ConnectionInterface;
use Convoy\Models\Filters\AllowedNullableFilter;
use Convoy\Transformers\Admin\AddressTransformer;
use Convoy\Exceptions\Repository\Proxmox\ProxmoxConnectionException;
use Convoy\Http\Requests\Admin\AddressPools\Addresses\UpdateAddressRequest;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class AddressController extends Controller
{
    public function __construct(private NetworkService $networkService, private ConnectionInterface $connection)
    {
    }

    public function index(Request $request, Node $node)
    {
        $addresses = QueryBuilder::for($node->addresses())
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

    public function update(UpdateAddressRequest $request, Node $node, Address $address)
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

    public function destroy(Node $node, Address $address)
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
