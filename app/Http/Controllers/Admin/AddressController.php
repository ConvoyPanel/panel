<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Ipam\GenerateAddressesAction;
use App\Data\Ipam\AddressMapData;
use App\Data\Ipam\AddressMapUnitData;
use App\Data\Ipam\BulkAddressResultData;
use App\Data\Ipam\GeneratedAddressesData;
use App\Data\Ipam\IpamAddressData;
use App\Data\PaginationMeta;
use App\Enums\Audit\AuditEvent;
use App\Enums\Network\AddressState;
use App\Enums\Network\AddressStateReason;
use App\Exceptions\Service\Address\AddressNotAvailableException;
use App\Exceptions\Service\Address\AddressNotReservedException;
use App\Exceptions\Service\Address\AddressReservedBySystemException;
use App\Facades\Audit;
use App\Http\Requests\Admin\Addresses\BulkAddressRequest;
use App\Http\Requests\Admin\Addresses\UpdateAddressRequest;
use App\Jobs\Server\SyncNetworkSettingsJob;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use App\Models\Server;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AddressController
{
    /** Above this a bulk action logs its range rather than every address in it. */
    private const AUDIT_ADDRESS_LIMIT = 50;

    public function __construct(
        private GenerateAddressesAction $generateAddressesAction,
        private ConnectionInterface $connection,
    ) {}

    public function index(Request $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
    {
        $addresses = QueryBuilder::for($addressBlock->addresses())
            ->with('server', 'addressBlock')
            // Address order, not insertion order. A list of a subnet that opens at .255 and counts
            // down is the reverse of how anyone reads a subnet; the inet column sorts natively.
            ->defaultSort('ip')
            ->allowedFilters(
                /*
                 * The search box on this screen is typed at partially — ".88", "203.0.113." — so an
                 * exact match answers nothing an operator actually asks. `host()` renders the inet
                 * column back to its bare address string, which is what LIKE needs; inet itself has
                 * no LIKE operator.
                 */
                AllowedFilter::callback('ip', function (Builder $query, $value): void {
                    $value = is_array($value) ? reset($value) : $value;

                    if ($value === null || $value === '') {
                        return;
                    }

                    $query->whereRaw('host(ip) LIKE ?', ['%'.$value.'%']);
                }),
                AllowedFilter::exact('server_id')->nullable(),
                /*
                 * The four states an operator sees, not the three the column stores: a system
                 * reservation is a reserved row the panel made and no one can release, so it
                 * filters as its own thing. Split exactly the way CountsAddressStates counts them,
                 * so a facet's count and the rows it returns can never disagree.
                 */
                AllowedFilter::callback('state', function (Builder $query, $value): void {
                    $tokens = array_filter(
                        (array) $value,
                        fn ($token) => $token !== null && $token !== '',
                    );

                    if ($tokens === []) {
                        return;
                    }

                    $query->where(function (Builder $outer) use ($tokens): void {
                        foreach ($tokens as $token) {
                            $outer->orWhere(function (Builder $inner) use ($token): void {
                                match ($token) {
                                    'assigned' => $inner->where('state', AddressState::Assigned),
                                    'available' => $inner->where('state', AddressState::Available),
                                    'system' => $inner
                                        ->where('state', AddressState::Reserved)
                                        ->where('state_reason', AddressStateReason::System),
                                    'reserved' => $inner
                                        ->where('state', AddressState::Reserved)
                                        ->where(fn (Builder $reason) => $reason
                                            ->whereNull('state_reason')
                                            ->orWhere('state_reason', '!=', AddressStateReason::System)),
                                    // An unrecognised token matches nothing. A filter that silently
                                    // widens the result set is worse than one that returns none.
                                    default => $inner->whereRaw('1 = 0'),
                                };
                            });
                        }
                    });
                }),
            )
            ->paginate(min($request->query('per_page', 50), 100))->appends(
                $request->query(),
            );

        return PaginationMeta::paginate($addresses, IpamAddressData::class);
    }

    public function generate(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
    {
        $result = $this->generateAddressesAction->execute($addressBlock);

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_GENERATED,
            subject: $addressBlock,
            properties: ['base_ip' => $addressBlock->base_ip],
        );

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
                $validated['state_reason'] = null;
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

            Audit::record(
                AuditEvent::ADMIN_ADDRESS_UPDATED,
                subject: $address,
                properties: [
                    'address' => $address->ip,
                    'changed' => array_keys($address->getChanges()),
                ],
            );
        });

        $address->load('server', 'addressBlock');

        return IpamAddressData::from($address);
    }

    public function reserve(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address)
    {
        if ($address->state !== AddressState::Available) {
            throw new AddressNotAvailableException;
        }

        $address->update([
            'state' => AddressState::Reserved,
            'state_reason' => AddressStateReason::Admin,
        ]);

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_RESERVED,
            subject: $address,
            properties: ['address' => $address->ip],
        );

        $address->load('server', 'addressBlock');

        return IpamAddressData::from($address);
    }

    public function unreserve(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address)
    {
        if ($address->state !== AddressState::Reserved) {
            throw new AddressNotReservedException;
        }

        // Network / broadcast / gateway are reserved by the panel, not by an operator — freeing them
        // would let the allocator hand a structural address to a VM.
        if ($address->isSystemReserved()) {
            throw new AddressReservedBySystemException;
        }

        $address->update(['state' => AddressState::Available, 'state_reason' => null]);

        Audit::record(
            AuditEvent::ADMIN_ADDRESS_UNRESERVED,
            subject: $address,
            properties: ['address' => $address->ip],
        );

        $address->load('server', 'addressBlock');

        return IpamAddressData::from($address);
    }

    /**
     * The block's whole address space, in address order, one entry per allocatable unit.
     *
     * The list answers "what is this address"; a paginated table cannot answer "where is the next
     * free run", which is the question a /24 is actually opened with. This returns every unit —
     * including the ones with no address row yet — so the UI can draw the space instead of asking
     * the operator to page through it.
     *
     * Units are placed by `unitIndexOf`, not by row order: generation writes in address order, but
     * one deletion would shift every later cell if position were inferred from the sequence.
     */
    public function map(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
    {
        $totalUnits = $addressBlock->totalUnits();

        if ($addressBlock->isSparse() || $totalUnits === null) {
            return (new AddressMapData(sparse: true, tooLarge: false, totalUnits: null, units: []))->toArray();
        }

        if ($totalUnits > AddressMapData::MAX_UNITS) {
            return (new AddressMapData(
                sparse: false,
                tooLarge: true,
                totalUnits: $totalUnits,
                units: [],
            ))->toArray();
        }

        // Every unit starts as a real position with no record behind it; the materialized rows are
        // then dropped onto their own indices.
        $units = [];

        for ($index = 0; $index < $totalUnits; $index++) {
            $units[$index] = new AddressMapUnitData(
                index: $index,
                state: 'ungenerated',
                // The unit is a real position whether or not a row exists for it, so it gets its
                // address either way — the map labels its rows from these.
                ip: $addressBlock->unitAddressAt($index),
                addressId: null,
                serverName: null,
            );
        }

        $addressBlock->addresses()->with('server:id,name')->chunkById(1000, function ($addresses) use (&$units, $addressBlock, $totalUnits): void {
            foreach ($addresses as $address) {
                $index = $addressBlock->unitIndexOf($address->ip);

                // An address outside the block's current geometry (the block was edited under it)
                // has no cell to sit in. Leaving it out beats drawing it in the wrong place.
                if ($index === null || $index < 0 || $index >= $totalUnits) {
                    continue;
                }

                $units[$index] = new AddressMapUnitData(
                    index: $index,
                    state: match (true) {
                        $address->state === AddressState::Assigned => 'assigned',
                        $address->isSystemReserved() => 'system',
                        $address->state === AddressState::Reserved => 'reserved',
                        default => 'available',
                    },
                    ip: $address->ip,
                    addressId: $address->id,
                    serverName: $address->server?->name,
                );
            }
        });

        return (new AddressMapData(
            sparse: false,
            tooLarge: false,
            totalUnits: $totalUnits,
            units: array_values($units),
        ))->toArray();
    }

    /**
     * Reserve, release or delete a selection of addresses in one request.
     *
     * Reserving `.2`–`.10` for infrastructure was nine trips through a row menu, and nine audit
     * entries. The rules are the single-address ones, applied by skipping rather than throwing: a
     * selection made by hand out of a table will contain rows the action does not apply to, and
     * rejecting the whole batch because one of them is system-reserved makes the action unusable.
     * What was skipped comes back in the result so the UI can say so.
     */
    public function bulk(BulkAddressRequest $request, AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock)
    {
        $action = $request->validated('action');

        // Scoped to the block in the URL, so an id from another block cannot be reached by
        // guessing it into the body.
        $addresses = $addressBlock->addresses()
            ->whereIn('id', $request->validated('ids'))
            ->get();

        $eligible = $addresses->filter(fn (Address $address) => match ($action) {
            'reserve' => $address->state === AddressState::Available,
            'release' => $address->state === AddressState::Reserved && ! $address->isSystemReserved(),
            // Deleting an address out from under a running server breaks its networking. The
            // single-address route allows it deliberately (one address, one decision); doing it to
            // a whole selection is a different risk, so assigned addresses are left alone here.
            'delete' => $address->state !== AddressState::Assigned,
            default => false,
        });

        $this->connection->transaction(function () use ($action, $eligible, $addressBlock): void {
            $ids = $eligible->pluck('id');

            if ($ids->isEmpty()) {
                return;
            }

            match ($action) {
                'reserve' => $addressBlock->addresses()->whereIn('id', $ids)->update([
                    'state' => AddressState::Reserved,
                    'state_reason' => AddressStateReason::Admin,
                ]),
                'release' => $addressBlock->addresses()->whereIn('id', $ids)->update([
                    'state' => AddressState::Available,
                    'state_reason' => null,
                ]),
                'delete' => $addressBlock->addresses()->whereIn('id', $ids)->delete(),
                default => null,
            };

            /*
             * One entry for the batch rather than one per address: the operator performed a single
             * action, and a log that reads as thousands of separate decisions hides that.
             *
             * The addresses themselves are listed only while the list is still worth reading. A
             * drag across a whole block would otherwise write tens of thousands of characters into
             * a properties column nobody can scan; past that, the range says the same thing.
             */
            $addresses = $eligible->pluck('ip');

            Audit::record(
                match ($action) {
                    'reserve' => AuditEvent::ADMIN_ADDRESS_RESERVED,
                    'release' => AuditEvent::ADMIN_ADDRESS_UNRESERVED,
                    default => AuditEvent::ADMIN_ADDRESS_DELETED,
                },
                subject: $addressBlock,
                properties: $addresses->count() <= self::AUDIT_ADDRESS_LIMIT
                    ? ['count' => $ids->count(), 'addresses' => $addresses->all()]
                    : [
                        'count' => $ids->count(),
                        'first' => $addresses->first(),
                        'last' => $addresses->last(),
                    ],
            );
        });

        return new BulkAddressResultData(
            action: $action,
            affected: $eligible->count(),
            skipped: $addresses->count() - $eligible->count(),
        );
    }

    public function destroy(AddressBlockGroup $addressBlockGroup, AddressBlock $addressBlock, Address $address): Response
    {
        $this->connection->transaction(function () use ($address) {
            $ip = $address->ip;

            $address->delete();

            Audit::record(
                AuditEvent::ADMIN_ADDRESS_DELETED,
                subject: $address,
                properties: ['address' => $ip],
            );

            if ($address->server) {
                dispatch(new SyncNetworkSettingsJob($address->server));
            }
        });

        return response()->noContent();
    }
}
