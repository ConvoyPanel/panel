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
        width: column.getSize(),
        zIndex: isPinned ? 1 : 0,
    }
}
