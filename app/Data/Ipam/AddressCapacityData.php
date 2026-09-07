<?php

namespace App\Data\Ipam;

use App\Models\AddressBlock;
use App\Models\AddressBlockGroup;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * One reading of how much of an address space is spoken for, shared by the pool row, the pool
 * header and the block page so the three cannot disagree about the same block.
 *
 * Nothing here decides how to draw it — that is the frontend's `features/ipam/capacity.ts`,
 * mirroring how `features/nodes/storages/capacity.ts` sits over the storage figures.
 */
#[MapInputName(SnakeCaseMapper::class)]
class AddressCapacityData extends Data
{
    public function __construct(
        /**
         * Allocatable units in the space (2^(prefix_to - prefix_from)).
         *
         * Null when the block is sparse: 2^64 units does not fit in a PHP int, and a percentage
         * of it would be meaningless anyway. A null total means "do not draw a meter", not zero.
         */
        public ?int $totalUnits,
        /** Sparse blocks are minted by the allocator on demand, never pre-materialized. */
        public bool $isSparse,
        /** Address rows that actually exist — for a dense block, its generation progress. */
        public int $generatedCount,
        public int $assignedCount,
        /** Held by an operator, and releasable. Excludes system reservations. */
        public int $reservedCount,
        /** Network / broadcast / gateway. Materialized, but can never be handed out. */
        public int $systemCount,
        public int $availableCount,
    ) {}

    public static function forBlock(AddressBlock $block): self
    {
        return new self(
            totalUnits: $block->totalUnits(),
            isSparse: $block->isSparse(),
            generatedCount: (int) ($block->addresses_count ?? 0),
            assignedCount: (int) ($block->assigned_addresses_count ?? 0),
            reservedCount: (int) ($block->reserved_addresses_count ?? 0),
            systemCount: (int) ($block->system_addresses_count ?? 0),
            availableCount: (int) ($block->available_addresses_count ?? 0),
        );
    }

    /**
     * A pool's capacity is the sum of its blocks'. One sparse block makes the whole pool's total
     * unknowable, which is the truthful answer — the UI then says so instead of drawing a bar
     * against a denominator it made up.
     */
    public static function forGroup(AddressBlockGroup $group): self
    {
        $blocks = $group->relationLoaded('addressBlocks')
            ? $group->addressBlocks
            : $group->addressBlocks()->get();

        $isSparse = $blocks->contains(fn (AddressBlock $block) => $block->isSparse());

        return new self(
            totalUnits: $isSparse
                ? null
                : (int) $blocks->sum(fn (AddressBlock $block) => $block->totalUnits() ?? 0),
            isSparse: $isSparse,
            generatedCount: (int) ($group->addresses_count ?? 0),
            assignedCount: (int) ($group->assigned_addresses_count ?? 0),
            reservedCount: (int) ($group->reserved_addresses_count ?? 0),
            systemCount: (int) ($group->system_addresses_count ?? 0),
            availableCount: (int) ($group->available_addresses_count ?? 0),
        );
    }
}
