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
        /**
         * Blocks in this pool the meter cannot measure — sparse ones, minted on demand. Their
         * addresses are excluded from the counts above rather than counted against a denominator
         * they are not part of, so the UI names them instead of quietly folding them in.
         */
        public int $sparseBlockCount = 0,
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
     * A pool's capacity is the sum of its sized blocks'.
     *
     * A sparse block is not folded in and not allowed to erase the answer: collapsing the whole
     * pool to "unknown" because one v6 block sits beside a /24 hides that the /24 is nearly full,
     * which is the thing the operator opened the screen for. The sparse blocks are counted and
     * named separately, and the counts above are already scoped to the dense ones.
     */
    public static function forGroup(AddressBlockGroup $group): self
    {
        $blocks = $group->relationLoaded('addressBlocks')
            ? $group->addressBlocks
            : $group->addressBlocks()->get();

        $dense = $blocks->reject(fn (AddressBlock $block) => $block->isSparse());
        $sparseCount = $blocks->count() - $dense->count();

        return new self(
            // Null only when there is no sized block at all — then there really is no denominator.
            totalUnits: $dense->isEmpty()
                ? null
                : (int) $dense->sum(fn (AddressBlock $block) => $block->totalUnits() ?? 0),
            isSparse: $dense->isEmpty() && $sparseCount > 0,
            generatedCount: (int) ($group->addresses_count ?? 0),
            assignedCount: (int) ($group->assigned_addresses_count ?? 0),
            reservedCount: (int) ($group->reserved_addresses_count ?? 0),
            systemCount: (int) ($group->system_addresses_count ?? 0),
            availableCount: (int) ($group->available_addresses_count ?? 0),
            sparseBlockCount: $sparseCount,
        );
    }
}
