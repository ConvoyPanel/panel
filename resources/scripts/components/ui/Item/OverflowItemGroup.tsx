import { cn } from '@/utils'
import { ReactNode, useState } from 'react'

import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/Sheet'

import { ItemGroup } from './Item.tsx'

interface Props {
    /** Pre-rendered, keyed Item rows. */
    rows: ReactNode[]
    /** Max rows shown inline before overflow moves behind the sheet. */
    max?: number
    /** Title for the overflow sheet. */
    title: string
    className?: string
}

/**
 * Renders up to `max` Item rows inline; any overflow goes behind a
 * "Show all N →" button that opens a right Sheet with the full list. Keeps the
 * card from growing unboundedly and shifting the page layout — same pattern as
 * the admin Needs-attention card.
 */
const OverflowItemGroup = ({ rows, max = 3, title, className }: Props) => {
    const [open, setOpen] = useState(false)
    const visible = rows.slice(0, max)
    const hasOverflow = rows.length > max

    return (
        <>
            <ItemGroup className={cn('gap-3', className)}>{visible}</ItemGroup>
            {/* Kept outside ItemGroup: a trigger isn't a list item, so it must
                not be a child of the group's role="list". */}
            {hasOverflow && (
                <button
                    type='button'
                    onClick={() => setOpen(true)}
                    className='hover:bg-muted mt-3 w-full rounded-md border py-2 text-sm font-semibold transition-colors'
                >
                    Show all {rows.length} &rarr;
                </button>
            )}

            <Sheet open={open} onOpenChange={setOpen}>
                {/* Keep the fixed sheet element itself non-scrolling and scroll
                    an inner div instead. On macOS Chrome, overscrolling a
                    `position: fixed` scroll container elastically translates the
                    whole element, revealing the dark overlay behind it. Bouncing
                    an inner child keeps the overscroll inside the sheet's own
                    background. */}
                <SheetContent
                    side='right'
                    /* Header uses py-4; the shared close button sits at top-4, so
                       nudge it to the header's vertical center. */
                    className='flex w-full flex-col overflow-hidden p-0 sm:max-w-md [&>button]:top-[1.375rem]'
                >
                    <SheetHeader className='border-b px-6 py-4'>
                        <SheetTitle>{title}</SheetTitle>
                    </SheetHeader>
                    <div className='flex-1 overflow-y-auto overscroll-contain px-6 py-4 mask-[linear-gradient(to_bottom,transparent,black_1rem,black_calc(100%-1rem),transparent)]'>
                        <ItemGroup className='gap-3'>{rows}</ItemGroup>
                    </div>
                </SheetContent>
            </Sheet>
        </>
    )
}

export default OverflowItemGroup
