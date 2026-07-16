<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Ipam\GenerateAddressesAction;
use App\Data\Ipam\GeneratedAddressesData;
use App\Data\Ipam\IpamAddressData;
use App\Data\PaginationMeta;
use App\Enums\Network\AddressState;
use App\Exceptions\Service\Address\AddressNotAvailableException;
use App\Exceptions\Service\Address\AddressNotReservedException;
use App\Http\Requests\Admin\Addresses\UpdateAddressRequest;
use App\Jobs\Server\SyncNetworkSettingsJob;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController
{
    public function __construct(
        private GenerateAddressesAction $generateAddressesAction,
        private ConnectionInterface $connection,
    ) {}

    public function index(Request $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
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

        return PaginationMeta::paginate($addresses, IpamAddressData::class);
    }

    public function generate(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
    {
        $result = $this->generateAddressesAction->execute($addressBlock);

        return GeneratedAddressesData::from($result);
    }

    public function update(UpdateAddressRequest $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address)
    {
        $validated = $request->validated();

        $this->connection->transaction(function () use ($address, $validated) {
            $oldServerId = $address->server_id;

            // Keep state in lock-step with the manual assignment (reserved addresses can't reach
            // here — UpdateAddressRequest rejects assigning them).
            if (array_key_exists('server_id', $validated)) {
                $validated['state'] = filled($validated['server_id'])
                    ? AddressState::Assigned
                    : AddressState::Available;
            }

            $address->update($validated);

            if (array_key_exists('server_id', $validated) && $oldServerId !== $validated['server_id']) {
                if (filled($oldServerId)) {
                    $oldServer = Server::find($oldServerId);
                    if ($oldServer) {
                        dispatch(new SyncNetworkSettingsJob($oldServer));
                    }
                }

                if (filled($validated['server_id'])) {
                    $newServer = Server::find($validated['server_id']);
                    if ($newServer) {
                        dispatch(new SyncNetworkSettingsJob($newServer));
                    }
                }
            }
        });

        $address->load('server', 'addressBlock');

        return IpamAddressData::from($address);
    }

    public function reserve(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address)
    {
        if ($address->state !== AddressState::Available) {
            throw new AddressNotAvailableException;
        }

        $address->update(['state' => AddressState::Reserved]);
        $address->load('server', 'addressBlock');

        return IpamAddressData::from($address);
    }

    public function unreserve(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address)
    {
        if ($address->state !== AddressState::Reserved) {
            throw new AddressNotReservedException;
        }

        $address->update(['state' => AddressState::Available]);
        $address->load('server', 'addressBlock');

        return IpamAddressData::from($address);
    }

    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address): Response
    {
        $this->connection->transaction(function () use ($address) {
            $address->delete();

            if ($address->server) {
                dispatch(new SyncNetworkSettingsJob($address->server));
            }
        });

        return response()->noContent();
    }
}
