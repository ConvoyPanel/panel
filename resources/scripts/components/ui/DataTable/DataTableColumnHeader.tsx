import { cn } from '@/utils'
import {
    IconArrowDown,
    IconArrowsSort,
    IconArrowUp,
} from '@tabler/icons-react'
import { Column } from '@tanstack/react-table'
import { HTMLAttributes } from 'react'

import { Button } from '@/components/ui/Button'

interface DataTableColumnHeaderProps<TData, TValue>
    extends HTMLAttributes<HTMLDivElement> {
    column: Column<TData, TValue>
    title: string
}

/**
 * Sortable column header. Toggles the column's sort state which — under the
 * table's `manualSorting` — flows through `tableProps` into the server query.
 * Opt-in per column; plain string headers stay non-sortable.
 */
const DataTableColumnHeader = <TData, TValue>({
    column,
    title,
    className,
}: DataTableColumnHeaderProps<TData, TValue>) => {
    if (!column.getCanSort()) {
        return <div className={className}>{title}</div>
    }

    const sorted = column.getIsSorted()

    return (
        <Button
            variant='ghost'
            className={cn('-ml-3 data-[state=open]:bg-accent', className)}
            onClick={() => column.toggleSorting(sorted === 'asc')}
        >
            <span>{title}</span>
            {sorted === 'desc' ? (
                <IconArrowDown className='size-4' />
            ) : sorted === 'asc' ? (
                <IconArrowUp className='size-4' />
            ) : (
                <IconArrowsSort className='size-4 opacity-50' />
            )}
        </Button>
    )
}

export default DataTableColumnHeader
