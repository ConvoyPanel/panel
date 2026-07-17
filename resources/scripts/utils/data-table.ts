import { Column } from '@tanstack/react-table'

export function getCommonPinningStyles<TData>({
    column,
    withBorder = false,
}: {
    column: Column<TData>
    /**
     * Show box shadow between pinned and scrollable columns.
     * @default false
     */
    withBorder?: boolean
}): React.CSSProperties {
    const isPinned = column.getIsPinned()
    const isLastLeftPinnedColumn =
        isPinned === 'left' && column.getIsLastColumn('left')
    const isFirstRightPinnedColumn =
        isPinned === 'right' && column.getIsFirstColumn('right')

    return {
        boxShadow: withBorder
            ? isLastLeftPinnedColumn
                ? '-4px 0 4px -4px var(--border) inset'
                : isFirstRightPinnedColumn
                  ? '4px 0 4px -4px var(--border) inset'
                  : undefined
            : undefined,
        left: isPinned === 'left' ? `${column.getStart('left')}px` : undefined,
        right:
            isPinned === 'right' ? `${column.getAfter('right')}px` : undefined,
        opacity: isPinned ? 0.97 : 1,
        position: isPinned ? 'sticky' : 'relative',
        // background: isPinned
        //     ? 'var(--background)'
        //     : 'var(--background)',
        // Only emit a width the column actually asked for. `getSize()` falls back
        // to react-table's default 150, and under `table-layout: auto` a declared
        // width is merely a suggestion that the browser tops up with a share of the
        // table's leftover space, proportional to the declared widths. Handing every
        // column a 150 therefore inflated the 32px select column to ~92px. Columns
        // that declare no size are left to size to their content instead.
        width: column.columnDef.size,
        zIndex: isPinned ? 1 : 0,
    }
}
