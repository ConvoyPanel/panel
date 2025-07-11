<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Ipam\GenerateAddressesAction;
use App\Http\Requests\Admin\Addresses\UpdateAddressRequest;
use App\Jobs\Server\SyncNetworkSettingsJob;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Server;
use App\Transformers\Admin\AddressTransformer;
use App\Transformers\Admin\GeneratedAddressesResultTransformer;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

use function dispatch;
use function fractal;
use function min;

class AddressController
{
    public function __construct(private GenerateAddressesAction $generateAddressesAction, private ConnectionInterface $connection) {}

    public function index(Request $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock): JsonResponse
    {
        $addresses = QueryBuilder::for($addressBlock->addresses())
            ->with('server', 'addressBlock')
            ->defaultSort('-id')
            ->allowedFilters(
                AllowedFilter::exact('ip'),
                AllowedFilter::exact('server_id')->nullable(),
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return fractal($addresses, new AddressTransformer)->parseIncludes($request->include)->respond();
    }

    public function generate(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock): JsonResponse
    {
        $result = $this->generateAddressesAction->execute($addressBlock);

        return fractal($result, new GeneratedAddressesResultTransformer)->respond();
    }

    public function update(UpdateAddressRequest $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address): JsonResponse
    {
        $validated = $request->validated();

        $this->connection->transaction(function () use ($address, $validated) {
            // Keep track of the old server ID before updating
            $oldServerId = $address->server_id;

            // Update the address with validated data
            $address->update($validated);

            // If server_id is present in validated data and has changed (including null changes)
            if (array_key_exists('server_id', $validated) && $oldServerId !== $validated['server_id']) {
                // Sync network settings for the old server if there was one
                if (filled($oldServerId)) {
                    $oldServer = Server::find($oldServerId);
                    if ($oldServer) {
                        dispatch(new SyncNetworkSettingsJob($oldServer));
                    }
                }

                // Sync network settings for the new server if one is assigned
                if (filled($validated['server_id'])) {
                    $newServer = Server::find($validated['server_id']);
                    if ($newServer) {
                        dispatch(new SyncNetworkSettingsJob($newServer));
                    }
                }
            }
        });

        return fractal($address->load('server', 'addressBlock'), new AddressTransformer)
            ->parseIncludes(['server', 'addressBlock'])
            ->respond();
    }

    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address): Response
    {
        $this->connection->transaction(function () use ($address) {
            // Delete the address
            $address->delete();

            // If the address had a server assigned, sync network settings for that server
            if ($address->server) {
                dispatch(new SyncNetworkSettingsJob($address->server));
            }
        });

        return response()->noContent();
    }
}
