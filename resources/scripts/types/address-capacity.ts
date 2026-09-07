/**
 * How much of an address space is spoken for. Returned on both an address block and the pool
 * above it, computed the same way on the server (`App\Data\Ipam\AddressCapacityData`) so a
 * pool row and the block page under it cannot disagree.
 */
export interface AddressCapacity {
    /**
     * Allocatable units in the space. Null when the block is sparse — 2^64 does not fit in an
     * int and a percentage of it means nothing. Null is "unknown", never zero.
     */
    totalUnits: number | null
    /** Sparse blocks are minted by the allocator on demand, never pre-materialised. */
    isSparse: boolean
    /** Address rows that exist. For a dense block, its generation progress. */
    generatedCount: number
    assignedCount: number
    /** Held by an operator, and releasable. Excludes system reservations. */
    reservedCount: number
    /** Network / broadcast / gateway. Materialised, but can never be handed out. */
    systemCount: number
    availableCount: number
    /**
     * Blocks in this pool the meter cannot measure — sparse ones, minted on demand. Their
     * addresses are excluded from the counts above rather than counted against a denominator they
     * are not part of, so the UI names them instead of quietly folding them in.
     */
    sparseBlockCount: number
}
