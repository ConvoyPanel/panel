import { useControllableState } from '@/hooks/use-controllable-state.ts'
import useTransientValue from '@/hooks/use-transient-value.ts'
import { DataTableProps } from '@/types/data-table.ts'
import { getCommonPinningStyles } from '@/utils/data-table.ts'
import {
    ColumnFiltersState,
    PaginationState,
    SortingState,
    Updater,
    VisibilityState,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table'
import { useState } from 'react'

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

const DataTable = <TData,>({
    data,
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

    const [sorting, setSorting] = useState<SortingState>([])
    const [rowSelection, setRowSelection] = useState({})
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {}
    )
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([])

    const resolvedData = !data ? [] : Array.isArray(data) ? data : data.items
    const pageCount =
        useTransientValue(
            !Array.isArray(data) ? data?.pagination.totalPages : null
        ) ?? -1

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
        data: resolvedData,
        pageCount,
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
        autoResetPageIndex: true,
        getCoreRowModel: getCoreRowModel(),
        //getPaginationRowModel: getPaginationRowModel(),
        enableRowSelection: true,
        onRowSelectionChange: setRowSelection,
        onSortingChange: setSorting,
        onColumnFiltersChange: setColumnFilters,
        onColumnVisibilityChange: setColumnVisibility,
        onGlobalFilterChange: setQuery,
        onPaginationChange,
        manualPagination: true,
        manualSorting: true,
        manualFiltering: true,
    })

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
                    <TableBody>
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
                                            colSpan={
                                                table._getColumnDefs().length
                                            }
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
                                                            // minWidth: shrinkZero
                                                            //     ? cellWidths[j]
                                                            //     : 'auto',
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
