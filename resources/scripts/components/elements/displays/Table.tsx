import {
    useReactTable,
    ColumnDef,
    getCoreRowModel,
    flexRender,
    createColumnHelper,
    AccessorFn,
} from '@tanstack/react-table'
import { ReactNode, useEffect, useMemo, useState } from 'react'
import styled from '@emotion/styled'
import tw from 'twin.macro'
import Checkbox from '@/components/elements/inputs/Checkbox'
import Scroller from '@/components/elements/Scroller'
import { DottedButton } from '@/components/servers/backups/BackupRow'
import Menu from '@/components/elements/Menu'

interface Column<T> {
    header: string
    accessor: string
    align?: 'left' | 'center' | 'right'
    cell?: ({ value }: { value: T }) => ReactNode
}

interface HeaderActionsProps {
    rows: Record<number, boolean>
}

interface RowActionsProps {
    row: number
}

interface Props<T> {
    columns: Column<T>[]
    data: T[]
    selectable?: boolean
    headerActions?: (payload: HeaderActionsProps) => ReactNode
    rowActions?: (payload: RowActionsProps) => ReactNode
}

const StyledTr = styled.tr`
    & > th:is(:first-of-type) {
        ${tw`rounded-l border-l`}
    }
    & > th:is(:last-child) {
        ${tw`rounded-r border-r`}
    }
`

const Table = <T,>({ columns: unparsedColumns, data, selectable, headerActions, rowActions }: Props<T>) => {
    const columnHelper = createColumnHelper<T>()

    const [rowSelection, setRowSelection] = useState<Record<number, boolean>>({})

    const columns = useMemo(() => {
        let parsedColumns = unparsedColumns.map(column =>
            columnHelper.accessor(column.accessor as unknown as AccessorFn<T, any>, {
                id: column.accessor,
                header: () => (
                    <div
                        className={`${column.align === undefined || column.align == 'left' ? 'text-left' : ''} ${
                            column.align === 'center' && 'text-center'
                        } ${column.align === 'right' && 'text-right'}`}
                    >
                        {column.header}
                    </div>
                ),
                cell: info => (column?.cell ? column.cell({ value: info.getValue() }) : info.getValue()),
            })
        )

        if (selectable) {
            parsedColumns = [
                columnHelper.display({
                    id: 'select',
                    header: ({ table }) => (
                        <div className='flex items-center'>
                            <Checkbox
                                checked={table.getIsAllRowsSelected()}
                                indeterminate={table.getIsSomeRowsSelected()}
                                onChange={table.getToggleAllRowsSelectedHandler()}
                            />
                        </div>
                    ),
                    cell: ({ row }) => (
                        <div className='flex items-center'>
                            <Checkbox
                                className='ml-[1px]'
                                checked={row.getIsSelected()}
                                indeterminate={row.getIsSomeSelected()}
                                onChange={row.getToggleSelectedHandler()}
                            />
                        </div>
                    ),
                }),
                ...parsedColumns,
            ]
        }

        if (headerActions || rowActions) {
            parsedColumns = [
                ...parsedColumns,
                columnHelper.display({
                    id: 'actions',
                    header: () =>
                        headerActions ? (
                                <Menu className='flex justify-center items-center'>
                                    <Menu.Button>
                                        <DottedButton className='relative' />
                                    </Menu.Button>{' '}
                                    <Menu.Items marginTop='3rem'>{headerActions({ rows: rowSelection })}</Menu.Items>
                                </Menu>
                        ) : null,
                    cell: ({ row }) =>
                        rowActions ? (
                            <Menu className='flex justify-center items-center'>
                                    <Menu.Button>
                                        <DottedButton className='relative mr-[1px]' />
                                    </Menu.Button>{' '}
                                    <Menu.Items marginTop='3rem'>{rowActions({ row: row.index })}</Menu.Items>
                                </Menu>
                        ) : null,
                }),
            ]
        }

        return parsedColumns
    }, [unparsedColumns, selectable])

    const table = useReactTable({
        data,
        columns,
        state: {
            rowSelection,
        },
        onRowSelectionChange: setRowSelection,
        getCoreRowModel: getCoreRowModel(),
    })

    return (
        <div className='overflow-auto scrollbar-hide'>
            <table className='border-separate w-full border-spacing-0'>
                <thead>
                    {table.getHeaderGroups().map(headerGroup => (
                        <StyledTr key={headerGroup.id}>
                            {headerGroup.headers.map(header => (
                                <th
                                    scope='col'
                                    className={`${header.id === 'select' && 'w-10 text-left'} ${
                                        header.id === 'actions' && 'right-0 sticky '
                                    } font-normal m-0 text-xs uppercase px-3 h-10 bg-accent-100 border-y border-accent-200`}
                                    key={header.id}
                                >
                                    {header.isPlaceholder
                                        ? null
                                        : flexRender(header.column.columnDef.header, header.getContext())}
                                </th>
                            ))}
                        </StyledTr>
                    ))}
                </thead>
                <tbody>
                    {table.getRowModel().rows.map(row => (
                        <tr key={row.id}>
                            {row.getVisibleCells().map(cell => (
                                <td
                                    className='overflow-hidden text-ellipsis whitespace-nowrap text-sm text-accent-600 px-3 h-[50px] border-b border-accent-200'
                                    key={cell.id}
                                >
                                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    )
}

export default Table
