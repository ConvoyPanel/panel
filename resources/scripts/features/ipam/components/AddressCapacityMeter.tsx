import {
    addressCapacity,
    addressCapacityReason,
} from '@/features/ipam/capacity.ts'
import { AddressCapacity } from '@/types/address-capacity.ts'
import { cn } from '@/utils'

import { SegmentedProgressBar } from '@/components/ui/Progress'

interface Props {
    capacity: AddressCapacity
    /** Taller bar for the block's own header, where it is the subject rather than a table cell. */
    size?: 'sm' | 'md'
    /**
     * The percent/free line under the bar. Off where a definition list beside the meter already
     * states those numbers — saying them twice in one card makes the reader check whether the two
     * disagree.
     */
    subline?: boolean
    className?: string
}

/**
 * The utilisation cell, used on the pool list, the pool header and the block header.
 *
 * It renders one of three things, never a fourth: a meter, or the reason there isn't one. A block
 * whose addresses have not been generated and a sparse block both lack a ratio, and both say so
 * in the same place in the same weight — so the column reads as one design rather than a bar
 * here and a badge there.
 */
const AddressCapacityMeter = ({
    capacity,
    size = 'sm',
    subline = true,
    className,
}: Props) => {
    const view = addressCapacity(capacity)

    if (!view.known) {
        return (
            <span className={cn('text-muted-foreground text-xs', className)}>
                {addressCapacityReason(view)}
            </span>
        )
    }

    return (
        <div className={className}>
            <SegmentedProgressBar
                segments={view.segments}
                className={size === 'md' ? 'h-2' : 'h-1.5'}
                aria-label={`${view.percent!.toFixed(0)}% of usable addresses are in use`}
            />
            {subline && (
                <p
                    className={
                        'text-muted-foreground mt-1.5 text-xs tabular-nums'
                    }
                >
                    {view.percent!.toFixed(1)}% · {view.free.toLocaleString()}{' '}
                    free
                    {view.ungenerated > 0 &&
                        ` · ${view.ungenerated.toLocaleString()} not generated`}
                    {view.sparseBlockCount > 0 &&
                        ` · ${view.sparseBlockCount} on demand`}
                </p>
            )}
        </div>
    )
}

export default AddressCapacityMeter
