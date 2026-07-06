import { useControllableState } from '@/hooks/use-controllable-state.ts'
import { DataTableProps } from '@/types/data-table.ts'
import { cn } from '@/utils'
import { getCommonPinningStyles } from '@/utils/data-table.ts'
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
import { useMemo } from 'react'

import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import Skeleton from '@/components/ui/Skeleton.tsx'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/Table'

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
            checked={
                table.getIsAllPageRowsSelected() ||
                (table.getIsSomePageRowsSelected() && 'indeterminate')
            }
            onCheckedChange={value =>
                table.toggleAllPageRowsSelected(!!value)
            }
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
    rightActions,
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
                | ((state: RowSelectionState) => void)
                | undefined,
        })

    const [columnVisibility, setColumnVisibility] =
        useControllableState<VisibilityState>({
            prop: _columnVisibility,
            defaultProp: {},
            onChange: _setColumnVisibility as
                | ((state: VisibilityState) => void)
                | undefined,
        })

    const [columnFilters, setColumnFilters] =
        useControllableState<ColumnFiltersState>({
            prop: _columnFilters,
            defaultProp: [],
            onChange: _setColumnFilters as
                | ((state: ColumnFiltersState) => void)
                | undefined,
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
        ? data?.pagination.totalPages ?? -1
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

    return (
        <div className='space-y-4'>
            {toolbar && (
                <DataTableToolbar
                    filterFields={filterFields}
                    searchable={searchable}
                    rightActions={rightActions}
                    table={table}
                />
            )}
            {showBulkBar && (
                <div className='flex items-center gap-2 rounded-md border bg-muted/40 px-3 py-2'>
                    <span className='text-sm text-muted-foreground'>
                        {selectedRows.length} selected
                    </span>
                    <div className='flex items-center gap-2'>
                        {bulkActions(selectedRows.map(row => row.original))}
                    </div>
                    <Button
                        variant='ghost'
                        size='sm'
                        className='ml-auto h-8'
                        onClick={() => table.resetRowSelection()}
                    >
                        Clear
                    </Button>
                </div>
            )}
            <div className='rounded-md border bg-background'>
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
                            isPlaceholderData &&
                                'opacity-60 transition-opacity'
                        )}
                    >
                        {data ? (
                            <>
                                {table.getRowModel().rows?.length ? (
                                    table.getRowModel().rows.map(row => (
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
                                    <TableRow>
                                        <TableCell
                                            colSpan={resolvedColumns.length}
                                            className='h-24 text-center'
                                        >
                                            No results.
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
