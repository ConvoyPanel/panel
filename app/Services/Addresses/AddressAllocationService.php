<?php

namespace App\Services\Addresses;

use App\Enums\Network\AddressVersion;
use App\Exceptions\Service\Address\InsufficientAddressesException;
use App\Models\Address;
use App\Models\AddressBlock;
use App\Models\NetworkInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AddressAllocationService
{
    /**
     * Allocate free addresses to satisfy the requested IPv4/IPv6 counts.
     *
     * Two paths, tried in order:
     *  1. **Reclaim** — pull existing free rows (`server_id IS NULL`) with a single indexed query
     *     per version: `... AND server_id IS NULL ORDER BY ip LIMIT n FOR UPDATE SKIP LOCKED`,
     *     backed by the partial inet index. This is the O(log N + n) hot path and covers both
     *     dense (pre-materialized) blocks and freed rows from sparse blocks. `SKIP LOCKED` makes
     *     concurrent allocations grab *different* rows (no double-assign).
     *  2. **Mint** — if a version is still short, mint fresh rows from its *sparse* blocks (large
     *     v4 / any v6, which are never pre-materialized because that would be billions of rows).
     *     See {@see mintFromSparseBlock()}.
     *
     * This replaced an O(address-space) offset walk — `for ($i = 0; $i < $range->getSize(); ...)`
     * keyed off `prefix_length_from`, effectively unbounded for IPv6 — that also raced.
     *
     * MUST run inside a database transaction: the reclaim row locks and the sparse per-block lock
     * hold the reservation only until the surrounding transaction commits, which is where the
     * caller stamps `server_id` via ServerNetworkService::syncAddresses(). ServerCreationService,
     * the sole caller, wraps the whole create in `DB::transaction()`.
     *
     * @return Collection<int, Address>
     *
     * @throws InsufficientAddressesException
     */
    public function handle(int $networkInterfaceId, int $requestedIpv4, int $requestedIpv6): Collection
    {
        $networkInterface = NetworkInterface::with('addressBlockGroups.addressBlocks')
            ->findOrFail($networkInterfaceId);

        /** @var Collection<string, Collection<int, AddressBlock>> $blocksByVersion */
        $blocksByVersion = $networkInterface->addressBlockGroups
            ->flatMap(fn ($group) => $group->addressBlocks)
            ->groupBy(fn (AddressBlock $block) => $block->version->value);

        return $this->allocateForVersion($blocksByVersion, AddressVersion::IPv4, $requestedIpv4)
            ->merge($this->allocateForVersion($blocksByVersion, AddressVersion::IPv6, $requestedIpv6))
            ->values();
    }

    /**
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

        /** @var Collection<int, AddressBlock> $blocks */
        $blocks = $blocksByVersion->get($version->value) ?? new Collection();

        if ($blocks->isEmpty()) {
            throw new InsufficientAddressesException();
        }

        // 1. Reclaim existing free rows across every block of this version.
        $result = Address::query()
            ->with('addressBlock')
            ->whereIn('address_block_id', $blocks->pluck('id')->all())
            ->whereNull('server_id')
            // ip is an inet column, so this orders numerically and is served without a sort by the
            // partial index addresses_free_by_block_ip_idx.
            ->orderBy('ip')
            ->limit($count)
            // FOR UPDATE SKIP LOCKED (Postgres + MySQL 8.0). Laravel has no skip-locked helper.
            ->lock('FOR UPDATE SKIP LOCKED')
            ->get();

        $needed = $count - $result->count();

        // 2. Mint the shortfall from sparse blocks (nothing to mint for dense blocks — their free
        //    rows were already all materialized and thus covered by the reclaim query above).
        foreach ($blocks->filter->isSparse() as $block) {
            if ($needed <= 0) {
                break;
            }

            $minted = $this->mintFromSparseBlock($block, $needed);
            $result = $result->merge($minted);
            $needed -= $minted->count();
        }

        if ($needed > 0) {
            throw new InsufficientAddressesException();
        }

        return $result;
    }

    /**
     * Mint up to $count fresh addresses at the top of a sparse block, appending after the highest
     * address already stored (`MAX(ip)`, served O(log N) by the unique (address_block_id, ip)
     * index). No offset walk and no pre-materialization: a v6 /64 hands out its first N addresses
     * in N index lookups, never 2^64 rows.
     *
     * The block row is locked `FOR UPDATE` first so concurrent allocations against the *same* sparse
     * block serialize their cursor advance — the next candidate is always `MAX(ip) + stride`, which
     * by definition can't already exist, so no unique conflict and no double-assign. (Freed rows
     * below the cursor re-enter via the reclaim query above, not here.)
     *
     * @return Collection<int, Address>
     */
    private function mintFromSparseBlock(AddressBlock $block, int $count): Collection
    {
        // Serialize cursor advancement for this block (held until the outer transaction commits).
        DB::table('address_blocks')->where('id', $block->id)->lockForUpdate()->first();

        $stride = $block->unitStride();
        $lastAddress = $block->lastAllocatableAddress();
        $mintedIds = [];

        for ($i = 0; $i < $count; $i++) {
            // Compute the next candidate (MAX(ip)+stride, or base_ip for an empty block), range-check
            // it against the block ceiling, and insert it — all in one statement so the cursor read
            // and the insert can't interleave. ON CONFLICT is a defensive no-op (see method doc).
            $row = DB::selectOne(
                <<<'SQL'
                WITH cand AS (
                    SELECT COALESCE(
                        (SELECT MAX(ip) FROM addresses WHERE address_block_id = ?) + ?::bigint,
                        ?::inet
                    ) AS ip
                ),
                chk AS (SELECT ip, ip <= ?::inet AS ok FROM cand),
                ins AS (
                    INSERT INTO addresses (address_block_id, ip, prefix_length, server_id)
                    SELECT ?, ip, ?, NULL FROM chk WHERE ok
                    ON CONFLICT (address_block_id, ip) DO NOTHING
                    RETURNING id
                )
                SELECT (SELECT ok FROM chk) AS ok, (SELECT id FROM ins) AS inserted_id
                SQL,
                [$block->id, $stride, $block->base_ip, $lastAddress, $block->id, $block->prefix_length_to],
            );

            // !ok = block exhausted; inserted_id null with ok = unexpected conflict — stop either way.
            if (! $row->ok || $row->inserted_id === null) {
                break;
            }

            $mintedIds[] = $row->inserted_id;
        }

        return Address::with('addressBlock')->findMany($mintedIds);
    }
}
