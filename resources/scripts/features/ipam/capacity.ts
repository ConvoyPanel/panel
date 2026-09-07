import type { AddressCapacity } from '@/types/address-capacity.ts'

import type { Segment } from '@/components/ui/Progress'

/**
 * One reading of an address space, shared by the pool row, the pool header and the block page so
 * the three cannot disagree about the same block. Mirrors `features/nodes/storages/capacity.ts`:
 * the figures come from the API, and nothing here recomputes them — this only decides how to
 * draw them.
 */
export interface AddressCapacityView {
    /**
     * Whether there is a real ratio to draw. A sparse block has no denominator and a block with
     * nothing generated has no rows; in both cases a bar pinned at zero would read as "empty"
     * when the truth is "unknown" and "not set up yet". No ratio, no bar.
     */
    known: boolean
    isSparse: boolean
    /** Every unit has an address row. Until then the block cannot hand out what it doesn't have. */
    isGenerated: boolean
    /** Allocatable units in the space, or null when sparse. */
    total: number | null
    /** What can ever be handed out: the total minus the panel's own reservations. */
    usable: number | null
    /** Assigned plus operator-held — the addresses somebody has taken. */
    inUse: number
    /**
     * Addresses that exist and are free to hand out. Deliberately *not* `usable - inUse`: for a
     * dense block the allocator can only hand out rows that have been generated, so ungenerated
     * units are not free, they are absent.
     */
    free: number
    /** Units with no address row yet. Zero once generation has finished. */
    ungenerated: number
    /** Share of usable space taken, or null when there is no denominator. */
    percent: number | null
    /** Blocks alongside this one that the meter cannot measure. Named, never folded in. */
    sparseBlockCount: number
    segments: Segment[]
}

const share = (part: number, total: number) =>
    total > 0 ? (part / total) * 100 : 0

export const addressCapacity = (
    capacity: AddressCapacity
): AddressCapacityView => {
    const {
        totalUnits,
        isSparse,
        generatedCount,
        assignedCount,
        reservedCount,
        systemCount,
        availableCount,
        sparseBlockCount,
    } = capacity

    const inUse = assignedCount + reservedCount
    const usable =
        totalUnits === null ? null : Math.max(totalUnits - systemCount, 0)
    const ungenerated =
        totalUnits === null ? 0 : Math.max(totalUnits - generatedCount, 0)
    const isGenerated = totalUnits !== null && generatedCount >= totalUnits
    const known = !isSparse && totalUnits !== null && generatedCount > 0

    return {
        known,
        isSparse,
        isGenerated,
        total: totalUnits,
        usable,
        inUse,
        free: availableCount,
        ungenerated,
        percent: usable && usable > 0 ? (inUse / usable) * 100 : null,
        sparseBlockCount,
        segments:
            totalUnits === null
                ? []
                : [
                      {
                          value: share(assignedCount, totalUnits),
                          color: 'var(--address-assigned)',
                          label: 'Assigned',
                      },
                      {
                          value: share(reservedCount, totalUnits),
                          color: 'var(--address-reserved)',
                          label: 'Reserved',
                      },
                      {
                          value: share(systemCount, totalUnits),
                          color: 'var(--address-system)',
                          label: 'System',
                      },
                  ].filter(segment => segment.value > 0),
    }
}

/**
 * The one line that replaces a meter when there isn't one to draw. Says which of the two
 * non-ratio cases this is, so the column reads as a single design with three states rather than
 * a bar here and a badge there.
 */
export const addressCapacityReason = (view: AddressCapacityView): string =>
    view.isSparse ? 'sparse · minted on demand' : 'no addresses generated'

/**
 * The "in use" cell: taken against what can ever be handed out.
 *
 * A sparse block has no denominator, so it states the count on its own rather than inventing a
 * total to divide by.
 */
export const addressInUseLabel = (view: AddressCapacityView): string =>
    view.usable === null
        ? `${view.inUse.toLocaleString()} assigned`
        : `${view.inUse.toLocaleString()} / ${view.usable.toLocaleString()}`
