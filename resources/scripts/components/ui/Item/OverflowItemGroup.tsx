import { ReactNode, useState } from 'react'

import { cn } from '@/utils'

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
            <ItemGroup className={cn('gap-3', className)}>
                {visible}
                {hasOverflow && (
                    <button
                        type='button'
                        onClick={() => setOpen(true)}
                        className='hover:bg-muted mt-1 w-full rounded-md border py-2 text-sm font-semibold transition-colors'
                    >
                        Show all {rows.length} &rarr;
                    </button>
                )}
            </ItemGroup>

            <Sheet open={open} onOpenChange={setOpen}>
                <SheetContent
                    side='right'
                    className='w-full overflow-y-auto sm:max-w-md'
                >
                    <SheetHeader>
                        <SheetTitle>{title}</SheetTitle>
                    </SheetHeader>
                    <ItemGroup className='mt-4 gap-3'>{rows}</ItemGroup>
                </SheetContent>
            </Sheet>
        </>
    )
}

export default OverflowItemGroup
