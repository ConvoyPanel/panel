import anchorStatus, { toneDotClass } from '@/features/anchors/status.ts'
import type { Anchor } from '@/features/anchors/types.ts'
import { cn } from '@/utils'

import { StatLabel } from '@/components/ui/Typography'

interface Props {
    anchor: Anchor
    className?: string
}

/**
 * A dot and a word, with the explanation beneath it. The dot is static: this
 * list refetches on its own, and a marker that moves draws the eye to whichever
 * anchor happens to be mid-install rather than to whichever one is broken.
 */
const AnchorStatusCell = ({ anchor, className }: Props) => {
    const status = anchorStatus(anchor)

    return (
        <div className={cn('flex min-w-0 items-start gap-2', className)}>
            <span
                className={cn(
                    'mt-[0.4rem] size-2 shrink-0 rounded-full',
                    toneDotClass[status.tone]
                )}
                aria-hidden
            />
            <div className='min-w-0'>
                <div className='leading-tight'>{status.label}</div>
                <StatLabel className='mt-0.5 text-xs'>
                    {status.detail}
                </StatLabel>
            </div>
        </div>
    )
}

export default AnchorStatusCell
