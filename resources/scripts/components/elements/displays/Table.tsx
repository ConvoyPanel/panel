import {
    useReactTable,
    ColumnDef,
    getCoreRowModel,
    flexRender,
    createColumnHelper,
    AccessorFn,
} from '@tanstack/react-table'
import { useMemo, useState } from 'react'
import styled from '@emotion/styled'
import tw from 'twin.macro'

interface Column {
    header: string
    accessor: string
    align?: 'left' | 'center' | 'right'
}

interface Props<T> {
    columns: Column[]
    data: T[]
    selectable?: boolean
}

const StyledTr = styled.tr`
    & > th:is(:first-of-type) {
        ${tw`rounded-l border-l`}
    }
    & > th:is(:last-child) {
        ${tw`rounded-r border-r`}
    }
`

const Table = <T,>({ columns: unparsedColumns, data, selectable }: Props<T>) => {
    const columnHelper = createColumnHelper<T>()

  const [rowSelection, setRowSelection] = useState({})

    const columns = useMemo(() => {
        return [...unparsedColumns.map(column =>
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
                cell: info => info.getValue(),
            })
        )]
    }, [unparsedColumns])

    const table = useReactTable({
        data,
        columns,
        state: {
            rowSelection
        },
        onRowSelectionChange: setRowSelection,
        getCoreRowModel: getCoreRowModel(),
    })

    return (
        <table className='border-separate w-full'>
            <thead>
                {table.getHeaderGroups().map(headerGroup => (
                    <StyledTr key={headerGroup.id}>
                        {headerGroup.headers.map(header => (
                            <th
                                scope='col'
                                className={`font-normal rounded text-xs uppercase px-[10px] h-10 bg-accent-100 border-y border-accent-200`}
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
                            <td className='text-sm text-accent-600 px-[10px] h-[50px] border-b border-accent-200' key={cell.id}>
                                {flexRender(cell.column.columnDef.cell, cell.getContext())}
                            </td>
                        ))}
                    </tr>
                ))}
            </tbody>
        </table>
    )
}

export default Table
