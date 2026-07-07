<?php

namespace App\Services\Addresses;

use App\Enums\Network\AddressVersion;
use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\NetworkInterface;
use Illuminate\Support\Collection;

class AddressAllocationService
{
    /**
     * Allocate free addresses to satisfy the requested IPv4/IPv6 counts.
     *
     * Selects from the pre-materialized free address rows (`server_id IS NULL`) with a single
     * indexed query per version, instead of walking the whole address space by offset. The old
     * implementation did a `for ($i = 0; $i < $range->getSize(); ...)` scan keyed off
     * `prefix_length_from` — O(address-space), and effectively unbounded for an IPv6 block
     * (2^64+ iterations). It also raced: two concurrent allocations computed the same first-free
     * offset and the unique index silently handed the loser an already-assigned row.
     *
     * `FOR UPDATE SKIP LOCKED` fixes both: concurrent allocations grab *different* rows (no
     * double-assign) and skip rows another in-flight allocation is holding. Consuming the
     * pre-materialized rows (produced by {@see \App\Actions\Ipam\GenerateAddressesAction}) also
     * reconciles the two mechanisms that previously disagreed — the allocator no longer
     * `firstOrCreate`s slots the generator never would have.
     *
     * MUST run inside a database transaction: the row locks reserve these still-unassigned rows
     * only until the surrounding transaction commits, which is where the caller stamps
     * `server_id` via {@see \App\Services\Servers\ServerNetworkService::syncAddresses()}.
     * {@see \App\Services\Servers\ServerCreationService::handle()}, the sole caller, wraps the
     * whole create in `DB::transaction()`.
     *
     * @return Collection<int, Address>
     *
     * @throws InsufficientAddressesException
     */
    public function handle(int $networkInterfaceId, int $requestedIpv4, int $requestedIpv6): Collection
    {
        $networkInterface = NetworkInterface::with([
            'addressBlockGroups.addressBlocks:id,address_block_group_id,version',
        ])->findOrFail($networkInterfaceId);

        /** @var Collection<string, Collection<int, AddressBlock>> $blocksByVersion */
        $blocksByVersion = $networkInterface->addressBlockGroups
            ->flatMap(fn ($group) => $group->addressBlocks)
            ->groupBy(fn (AddressBlock $block) => $block->version->value);

        return $this->allocateForVersion($blocksByVersion, AddressVersion::IPv4, $requestedIpv4)
            ->merge($this->allocateForVersion($blocksByVersion, AddressVersion::IPv6, $requestedIpv6))
            ->values();
    }

    /**
     * Grab up to $count free rows across every block of the given version, atomically.
     *
     * @param  Collection<string, Collection<int, AddressBlock>>  $blocksByVersion
     * @return Collection<int, Address>
     *
     * @throws InsufficientAddressesException
     */
    private function allocateForVersion(Collection $blocksByVersion, AddressVersion $version, int $count): Collection
    {
        if ($count <= 0) {
            return new Collection();
        }

        $blockIds = ($blocksByVersion->get($version->value) ?? new Collection())
            ->pluck('id')
            ->all();

        if (empty($blockIds)) {
            throw new InsufficientAddressesException();
        }

        $addresses = Address::query()
            ->with('addressBlock')
            ->whereIn('address_block_id', $blockIds)
            ->whereNull('server_id')
            ->orderBy('id')
            ->limit($count)
            // FOR UPDATE SKIP LOCKED is supported by both Postgres and MySQL 8.0. Laravel has no
            // dedicated skip-locked helper, so the clause is passed explicitly.
            ->lock('FOR UPDATE SKIP LOCKED')
            ->get();

        if ($addresses->count() < $count) {
            throw new InsufficientAddressesException();
        }

        return $addresses;
    }
}
