import { cn } from '@/utils'
import { Key, ReactNode } from 'react'

import {
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'

/**
 * A table that is the card, rather than a table inside one.
 *
 * Half a dozen cards had each arrived at this shape on their own — storage
 * consumers, node storages, a user's servers, the admin nodes tile, a server's
 * disks — and each re-derived the same details: `CardContent` unpadded so the
 * rows can bleed, `pl-4` on the first cell and `pr-4` on the last to meet the
 * card's own padding, `[&_tr:last-child]:border-0` so the list doesn't close
 * with a rule, `hover:bg-transparent` on the header row. Re-deriving that is
 * how one card ends up with a tinted header strip and a bordered inset while
 * its neighbour has neither.
 *
 * What the component decides, so a call site cannot get it wrong:
 *
 *  - **No frame.** The card is the frame. No inset border, no fill behind the
 *    header — a header band belongs to a page-sized table, not to a card.
 *  - **Columns hug their content**, and exactly one takes the slack (the last,
 *    unless a column says `fill`). Without that, an auto table spreads four
 *    short values across the whole card and a row stops reading as one record.
 *    A single-column table gets an empty spacer instead, so its values stay
 *    left rather than centring themselves across the card.
 *  - **The header row appears only if a labelled column has company.** A lone
 *    column of addresses under the word "Address" is the band that made the old
 *    IPAM card top-heavy; a column tells you what it is once it has a
 *    neighbour to be told apart from.
 *  - **It scrolls rather than overhangs.** Machine values don't wrap, and four
 *    columns of them outrun a phone-width card.
 *
 * @example
 * <CardContent className={'p-0'}>
 *     <CardTable
 *         caption={'IP addresses allocated to this server'}
 *         rows={addresses}
 *         rowKey={address => address.id}
 *         columns={columns}
 *         footer={<><p>Gateway 203.0.113.1</p><p>5 of 7</p></>}
 *     />
 * </CardContent>
 */
export interface CardTableColumn<T> {
    key: string
    /** Column label. Omit for a column that names itself — a chip, an action. */
    header?: ReactNode
    /** Takes the row's leftover width. Defaults to the last column. */
    fill?: boolean
    align?: 'left' | 'right'
    /** Classes for this column's body cells. */
    className?: string
    /** Classes for this column's header cell, where they must differ. */
    headerClassName?: string
    cell: (row: T) => ReactNode
}

interface Props<T> {
    rows: T[]
    columns: CardTableColumn<T>[]
    rowKey: (row: T) => Key
    /** Names the table for screen readers. Required: a table is not a layout. */
    caption: string
    /** Replaces the table when there are no rows. */
    empty?: ReactNode
    /** A muted strip closing the table — shared facts on the left, a count on
     *  the right. Two children lay themselves out; one sits on the left. */
    footer?: ReactNode
    className?: string
}

const CardTable = <T,>({
    rows,
    columns,
    rowKey,
    caption,
    empty,
    footer,
    className,
}: Props<T>) => {
    if (rows.length === 0 && empty) {
        return <div className={'px-4 pb-4'}>{empty}</div>
    }

    const declaredFill = columns.findIndex(column => column.fill)
    const fill =
        declaredFill >= 0
            ? declaredFill
            : columns.length > 1
              ? columns.length - 1
              : -1

    // The first and last cells meet the card's `p-4`; the rest are spaced from
    // each other. A single column is both first and last, so the two apply.
    const padding = (index: number) =>
        cn(
            'px-3',
            index === 0 && 'pl-4',
            index === columns.length - 1 && fill >= 0 && 'pr-4'
        )

    // One column needs no header: there is nothing to tell it apart from, and
    // the card's own title already says what these are. Labels start earning
    // their row at two.
    const labelled =
        columns.length > 1 &&
        columns.some(column => column.header !== undefined)

    return (
        <>
            <div className={cn('overflow-x-auto', className)}>
                <table className={'w-full caption-bottom border-t text-sm'}>
                    <caption className={'sr-only'}>{caption}</caption>
                    {labelled && (
                        <TableHeader>
                            <TableRow className={'hover:bg-transparent'}>
                                {columns.map((column, index) => (
                                    <TableHead
                                        key={column.key}
                                        className={cn(
                                            'h-8 text-xs whitespace-nowrap',
                                            padding(index),
                                            index === fill ? 'w-full' : 'w-px',
                                            column.align === 'right' &&
                                                'text-right',
                                            column.headerClassName
                                        )}
                                    >
                                        {column.header}
                                    </TableHead>
                                ))}
                                {fill < 0 && <th className={'w-full'} />}
                            </TableRow>
                        </TableHeader>
                    )}
                    <TableBody>
                        {rows.map(row => (
                            <TableRow key={rowKey(row)}>
                                {columns.map((column, index) => (
                                    <TableCell
                                        key={column.key}
                                        className={cn(
                                            'py-3',
                                            padding(index),
                                            index === fill ? 'w-full' : 'w-px',
                                            column.align === 'right' &&
                                                'text-right',
                                            column.className
                                        )}
                                    >
                                        {column.cell(row)}
                                    </TableCell>
                                ))}
                                {/* Nothing to fill with: an empty cell takes
                                    the slack so a one-column table stays a
                                    column rather than a centred smear. */}
                                {fill < 0 && <td className={'w-full'} />}
                            </TableRow>
                        ))}
                    </TableBody>
                </table>
            </div>

            {footer && (
                <div
                    className={
                        'text-muted-foreground flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-t px-4 py-3 text-xs'
                    }
                >
                    {footer}
                </div>
            )}
        </>
    )
}

export default CardTable
