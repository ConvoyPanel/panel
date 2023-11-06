import styled from '@emotion/styled'
import {
    AccessorFn,
    createColumnHelper,
    flexRender,
    getCoreRowModel,
    useReactTable,
} from '@tanstack/react-table'
import { ReactNode, useMemo, useState } from 'react'
import tw from 'twin.macro'

import DottedButton from '@/components/elements/DottedButton'
import Menu from '@/components/elements/Menu'
import Checkbox from '@/components/elements/inputs/Checkbox'

export type Alignment = 'left' | 'center' | 'right'

export interface Column<T, K extends keyof T> {
    header: string
    accessor: K
    align?: Alignment
    cell?: (payload: { value: T[K]; row: T }) => ReactNode
    overflow?: boolean
}

export type ColumnArray<T> = { [K in keyof T]-?: Column<T, K> }[keyof T][]

interface HeaderActionsProps {
    rows: Record<number, boolean>
}

export interface RowActionsProps<T> {
    row: T
}

interface Props<T> {
    columns: ColumnArray<T>
    data: T[]
    selectable?: boolean
    headerActions?: (payload: HeaderActionsProps) => ReactNode
    rowActions?: (payload: RowActionsProps<T>) => ReactNode
    minWidth?: number
}

const StyledTr = styled.tr`
    & > th:is(:first-of-type) {
        ${tw`rounded-l border-l`}
    }
    & > th:is(:last-child) {
        ${tw`rounded-r border-r`}
    }
`

type ThType = 'actions' | 'select'

const getThClasses = (
    type: ThType,
    align?: Alignment,
    hasHeaderActions?: boolean
) => {
    let classes =
        'font-normal whitespace-nowrap m-0 text-xs uppercase px-3 h-10 bg-accent-100 border-y border-accent-200'

    switch (type) {
        case 'actions':
            // only add if hasHeaderActions
            if (hasHeaderActions) classes += ' sticky right-0'
            break
        case 'select':
            classes += ' w-10 text-left'
            break
    }

    switch (align) {
        case 'center':
            classes += ' text-center'
            break
        case 'right':
            classes += ' text-right'
            break
        default:
            classes += ' text-left'
            break
    }

    return classes
}

interface ActionProps {
    children?: ReactNode
}

export const Actions = ({ children }: ActionProps) => (
    <Menu position='bottom-end' width={200} withinPortal>
        <Menu.Target>
            <DottedButton />
        </Menu.Target>
        <Menu.Dropdown>{children}</Menu.Dropdown>
    </Menu>
)

const Table = <T,>({
    columns: unparsedColumns,
    data,
    selectable,
    headerActions,
    rowActions,
    minWidth,
}: Props<T>) => {
    const columnHelper = createColumnHelper<T>()

    const [rowSelection, setRowSelection] = useState<Record<number, boolean>>(
        {}
    )

    const columns = useMemo(() => {
        let parsedColumns = unparsedColumns.map(column => {
            const { accessor, header, cell, ...rest } = column

            return columnHelper.accessor(
                accessor as unknown as AccessorFn<T, any>,
                {
                    // @ts-expect-error
                    id: accessor,
                    header,
                    cell: info =>
                        cell
                            ? cell({
                                  value: info.getValue(),
                                  row: info.row.original,
                              })
                            : info.getValue(),
                    meta: rest,
                }
            )
        })

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
                            <Menu width={200} withinPortal>
                                <Menu.Target>
                                    <DottedButton className='relative' />
                                </Menu.Target>
                                <Menu.Dropdown>
                                    {headerActions({ rows: rowSelection })}
                                </Menu.Dropdown>
                            </Menu>
                        ) : null,
                    cell: ({ row }) =>
                        rowActions ? rowActions({ row: row.original }) : null,

                    meta: {
                        overflow: true,
                    },
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
        <div className='overflow-y-visible overflow-x-auto scrollbar-hide'>
            <table
                style={{
                    minWidth: minWidth ? `${minWidth}px` : '650px',
                }}
                className='relative border-separate w-full border-spacing-0'
            >
                <thead>
                    {table.getHeaderGroups().map(headerGroup => (
                        <StyledTr key={headerGroup.id}>
                            {headerGroup.headers.map(header => (
                                <th
                                    scope='col'
                                    className={getThClasses(
                                        header.column.columnDef.id as ThType,
                                        header.column.columnDef.meta?.align,
                                        Boolean(headerActions)
                                    )}
                                    key={header.id}
                                >
                                    {header.isPlaceholder
                                        ? null
                                        : flexRender(
                                              header.column.columnDef.header,
                                              header.getContext()
                                          )}
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
                                    className={`${
                                        cell.column.columnDef.meta?.overflow
                                            ? ''
                                            : 'overflow-hidden text-ellipsis whitespace-nowrap'
                                    } ${
                                        cell.column.columnDef.meta?.align ===
                                        'center'
                                            ? 'text-center'
                                            : cell.column.columnDef.meta
                                                  ?.align == 'right'
                                            ? 'text-right'
                                            : ''
                                    } ${
                                        cell.column.id === 'actions' &&
                                        rowActions
                                            ? 'bg-background sticky right-0'
                                            : ''
                                    } text-sm text-accent-600 px-3 h-[50px] border-b border-accent-200`}
                                    key={cell.id}
                                >
                                    {flexRender(
                                        cell.column.columnDef.cell,
                                        cell.getContext()
                                    )}
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
