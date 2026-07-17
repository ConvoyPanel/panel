import { useControllableState } from '@/hooks/use-controllable-state.ts'
import { DataTableProps } from '@/types/data-table.ts'
import { cn } from '@/utils'
import { getCommonPinningStyles } from '@/utils/data-table.ts'
import { IconX } from '@tabler/icons-react'
import {
    ColumnDef,
    ColumnFiltersState,
    OnChangeFn,
    PaginationState,
    RowSelectionState,
    SortingState,
    Updater,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table'
import { Fragment, useMemo } from 'react'

import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { CollectionErrorState } from '@/components/ui/EmptyStates'
import { Item, ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'

import DataTableFilteredEmpty from './DataTableFilteredEmpty.tsx'
import DataTablePagination from './DataTablePagination'
import DataTableToolbar from './DataTableToolbar.tsx'

/**
 * Adapts a plain `(value: T) => void` setter into react-table's `OnChangeFn<T>`,
 * resolving the incoming updater against the current value.
 */
const asChangeFn =
    <T,>(current: T, set: (value: T) => void): OnChangeFn<T> =>
    updater =>
        set(
            typeof updater === 'function'
                ? (updater as (old: T) => T)(current)
                : updater
        )

const selectionColumn = <TData,>(): ColumnDef<TData> => ({
    id: 'select',
    header: ({ table }) => (
        <Checkbox
            checked={table.getIsAllPageRowsSelected()}
            indeterminate={table.getIsSomePageRowsSelected()}
            onCheckedChange={value => table.toggleAllPageRowsSelected(!!value)}
            aria-label='Select all'
            className='translate-y-[2px]'
        />
    ),
    cell: ({ row }) => (
        <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={value => row.toggleSelected(!!value)}
            aria-label='Select row'
            className='translate-y-[2px]'
        />
    ),
    enableSorting: false,
    enableHiding: false,
    size: 32,
})

const DataTable = <TData,>({
    data,
    columns,
    initialState,
    filterFields,
    skeletonRows = 10,
    perPageOptions,
    showPerPageOptions,
    toolbar,
    searchable,
    query: _query,
    setQuery: _setQuery,
    paginated,
    page: _page,
    setPage: _setPage,
    perPage: _perPage,
    setPerPage: _setPerPage,
    sorting: _sorting,
    setSorting: _setSorting,
    columnFilters: _columnFilters,
    setColumnFilters: _setColumnFilters,
    rowSelection: _rowSelection,
    setRowSelection: _setRowSelection,
    columnVisibility: _columnVisibility,
    setColumnVisibility: _setColumnVisibility,
    enableRowSelection = false,
    bulkActions,
    isPlaceholderData,
    isError = false,
    onRetry,
    errorState,
    rightActions,
    emptyState,
    filteredEmptyState,
    mobileRow,
    ...props
}: DataTableProps<TData>) => {
    const [query, setQuery] = useControllableState({
        prop: _query,
        defaultProp: '',
        onChange: _setQuery,
    })

    const [page, setPage] = useControllableState({
        prop: _page,
        defaultProp: 1,
        onChange: _setPage,
    })

    const [perPage, setPerPage] = useControllableState({
        prop: _perPage,
        defaultProp: 20,
        onChange: _setPerPage,
    })

    const [sorting, setSorting] = useControllableState<SortingState>({
        prop: _sorting,
        defaultProp: [],
        onChange: _setSorting as ((state: SortingState) => void) | undefined,
    })

    const [rowSelection, setRowSelection] =
        useControllableState<RowSelectionState>({
            prop: _rowSelection,
            defaultProp: {},
            onChange: _setRowSelection as
                ((state: RowSelectionState) => void) | undefined,
        })

    const [columnVisibility, setColumnVisibility] =
        useControllableState<VisibilityState>({
            prop: _columnVisibility,
            defaultProp: {},
            onChange: _setColumnVisibility as
                ((state: VisibilityState) => void) | undefined,
        })

    const [columnFilters, setColumnFilters] =
        useControllableState<ColumnFiltersState>({
            prop: _columnFilters,
            defaultProp: [],
            onChange: _setColumnFilters as
                ((state: ColumnFiltersState) => void) | undefined,
        })

    const resolvedColumns = useMemo(
        () =>
            enableRowSelection
                ? [selectionColumn<TData>(), ...columns]
                : columns,
        [enableRowSelection, columns]
    )

    const resolvedData = !data ? [] : Array.isArray(data) ? data : data.items
    const pageCount = !Array.isArray(data)
        ? (data?.pagination.totalPages ?? -1)
        : -1

    const pagination: PaginationState = {
        pageIndex: page! - 1,
        pageSize: perPage!,
    }

    const onPaginationChange = (updaterOrValue: Updater<PaginationState>) => {
        const updatedPagination =
            updaterOrValue instanceof Function
                ? updaterOrValue(pagination!)
                : updaterOrValue

        setPage(updatedPagination.pageIndex + 1)
        setPerPage(updatedPagination.pageSize)
    }

    const table = useReactTable({
        ...props,
        columns: resolvedColumns,
        data: resolvedData,
        pageCount,
        defaultColumn: {
            /*
             * react-table's column-sizing feature merges `size: 150` into every
             * column's def, so a column cannot otherwise be asked whether it
             * actually declared a width. Blanking it here means `columnDef.size`
             * is set only where a column really asked for one, which is what
             * `getCommonPinningStyles` emits a CSS width from. `getSize()` still
             * falls back to 150 for the pinning offsets that need a number.
             */
            size: undefined,
            ...props.defaultColumn,
        },
        getRowId: enableRowSelection
            ? row => String((row as { id?: string | number }).id)
            : undefined,
        initialState: {
            columnPinning: { right: ['actions'] },
            ...initialState,
        },
        state: {
            sorting,
            columnVisibility,
            rowSelection,
            columnFilters,
            globalFilter: query,
            pagination,
        },
        getCoreRowModel: getCoreRowModel(),
        enableRowSelection,
        onRowSelectionChange: asChangeFn(rowSelection ?? {}, setRowSelection),
        onSortingChange: asChangeFn(sorting ?? [], setSorting),
        onColumnFiltersChange: asChangeFn(
            columnFilters ?? [],
            setColumnFilters
        ),
        onColumnVisibilityChange: asChangeFn(
            columnVisibility ?? {},
            setColumnVisibility
        ),
        onGlobalFilterChange: setQuery,
        onPaginationChange,
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
    })

    const selectedRows = table.getSelectedRowModel().rows
    const showBulkBar =
        enableRowSelection && !!bulkActions && selectedRows.length > 0

    const rows = table.getRowModel().rows
    const isFiltered = !!query || (columnFilters?.length ?? 0) > 0
    const hasNoRows = !!data && rows.length === 0

    const clearFilters = () => {
        setQuery('')
        setColumnFilters([])
    }

    if (!data && isError) {
        return (
            <>
                {errorState ?? (
                    <CollectionErrorState
                        className='bg-background rounded-md border'
                        onRetry={onRetry}
                    />
                )}
            </>
        )
    }

    /**
     * An unfiltered empty collection has nothing to search, filter, or
     * paginate, so the contextual empty state stands in for the whole table
     * rather than rendering as a row inside an otherwise-functional shell.
     */
    if (hasNoRows && !isFiltered && emptyState) {
        return <>{emptyState}</>
    }

    const noRowsContent =
        hasNoRows && isFiltered
            ? (filteredEmptyState ?? (
                  <DataTableFilteredEmpty onClear={clearFilters} />
              ))
            : null

    return (
        <div className='@container space-y-4'>
            {toolbar && (
                <DataTableToolbar
                    filterFields={filterFields}
                    searchable={searchable}
                    rightActions={rightActions}
                    table={table}
                />
            )}
            {enableRowSelection && !!bulkActions && (
                /*
                 * The selection bar floats rather than sitting in the flow: as a
                 * flow child it pushed the table (and the whole page under it)
                 * down by its own height the instant a row was ticked. Fixed to
                 * the viewport also keeps it reachable on a long table, where a
                 * bar pinned above the rows would have scrolled out of sight
                 * while the selection was still live.
                 *
                 * Kept mounted and faded so it has an exit transition as well as
                 * an enter one; `pointer-events-none` on the wrapper means the
                 * idle state never swallows clicks meant for the page.
                 */
                <div
                    aria-hidden={!showBulkBar}
                    className={cn(
                        'pointer-events-none fixed inset-x-0 bottom-6 z-50 flex justify-center px-4',
                        'transition-[opacity,translate] duration-150 ease-out',
                        showBulkBar
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-2 opacity-0'
                    )}
                >
                    <div
                        className={cn(
                            'bg-popover flex max-w-full items-center gap-2 rounded-full border py-1.5 pr-1.5 pl-4 shadow-lg',
                            showBulkBar && 'pointer-events-auto'
                        )}
                    >
                        <span className='text-sm font-medium tabular-nums whitespace-nowrap'>
                            {selectedRows.length} selected
                        </span>
                        <span
                            aria-hidden
                            className='bg-border h-5 w-px shrink-0'
                        />
                        <div className='flex items-center gap-2'>
                            {bulkActions(selectedRows.map(row => row.original))}
                        </div>
                        <span
                            aria-hidden
                            className='bg-border h-5 w-px shrink-0'
                        />
                        <Button
                            variant='ghost'
                            size='icon'
                            className='size-8 shrink-0 rounded-full'
                            onClick={() => table.resetRowSelection()}
                            aria-label='Clear selection'
                        >
                            <IconX className='size-4' />
                        </Button>
                    </div>
                </div>
            )}
            <div
                className={cn(
                    'bg-background rounded-md border',
                    mobileRow && 'hidden @md:block'
                )}
            >
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map(headerGroup => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map(header => {
                                    return (
                                        <TableHead
                                            key={header.id}
                                            colSpan={header.colSpan}
                                            style={{
                                                ...getCommonPinningStyles({
                                                    column: header.column,
                                                }),
                                                textAlign:
                                                    header.column.columnDef.meta
                                                        ?.align ?? 'left',
                                            }}
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext()
                                                  )}
                                        </TableHead>
                                    )
                                })}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody
                        className={cn(
                            isPlaceholderData && 'opacity-60 transition-opacity'
                        )}
                    >
                        {data ? (
                            <>
                                {rows.length ? (
                                    rows.map(row => (
                                        <TableRow
                                            key={row.id}
                                            data-state={
                                                row.getIsSelected() &&
                                                'selected'
                                            }
                                        >
                                            {row.getVisibleCells().map(cell => (
                                                <TableCell
                                                    key={cell.id}
                                                    style={{
                                                        ...getCommonPinningStyles(
                                                            {
                                                                column: cell.column,
                                                            }
                                                        ),
                                                        textAlign:
                                                            cell.column
                                                                .columnDef.meta
                                                                ?.align ??
                                                            'left',
                                                    }}
                                                >
                                                    {flexRender(
                                                        cell.column.columnDef
                                                            .cell,
                                                        cell.getContext()
                                                    )}
                                                </TableCell>
                                            ))}
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow className='hover:bg-transparent'>
                                        <TableCell
                                            colSpan={resolvedColumns.length}
                                            className='p-0'
                                        >
                                            {noRowsContent ?? (
                                                <div className='text-muted-foreground flex h-24 items-center justify-center text-center'>
                                                    No results.
                                                </div>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                )}
                            </>
                        ) : (
                            <>
                                {Array.from({ length: skeletonRows }).map(
                                    (_, i) => (
                                        <TableRow
                                            key={i}
                                            className='hover:bg-transparent'
                                        >
                                            {table
                                                .getAllColumns()
                                                .filter(column =>
                                                    column.getIsVisible()
                                                )
                                                .map(col => (
                                                    <TableCell
                                                        key={col.id}
                                                        style={{
                                                            width:
                                                                col.columnDef
                                                                    .meta
                                                                    ?.skeletonWidth ??
                                                                'auto',
                                                        }}
                                                    >
                                                        <Skeleton className='h-6 w-full' />
                                                    </TableCell>
                                                ))}
                                        </TableRow>
                                    )
                                )}
                            </>
                        )}
                    </TableBody>
                </Table>
            </div>
            {/* Kept outside ItemGroup: an empty state isn't a list item, so it
                must not be a child of the group's role="list". */}
            {mobileRow && noRowsContent && (
                <div className='@md:hidden'>{noRowsContent}</div>
            )}
            {mobileRow && !noRowsContent && (
                <ItemGroup
                    className={cn(
                        'gap-3 @md:hidden',
                        isPlaceholderData &&
                            data &&
                            'opacity-60 transition-opacity'
                    )}
                >
                    {data ? (
                        rows.length ? (
                            rows.map(row => (
                                <Fragment key={row.id}>
                                    {mobileRow(row)}
                                </Fragment>
                            ))
                        ) : (
                            <Item
                                variant={'muted'}
                                size={'sm'}
                                className='text-muted-foreground justify-center'
                            >
                                No results.
                            </Item>
                        )
                    ) : (
                        Array.from({ length: skeletonRows }).map((_, i) => (
                            <Item
                                key={i}
                                variant={'muted'}
                                size={'sm'}
                                className='h-16'
                            >
                                <Skeleton className='h-6 w-full' />
                            </Item>
                        ))
                    )}
                </ItemGroup>
            )}
            {paginated && (
                <DataTablePagination
                    table={table}
                    perPageOptions={perPageOptions}
                    showPerPageOptions={showPerPageOptions}
                />
            )}
        </div>
    )
}

export default DataTable
