import { DataTableFilterField } from '@/types/data-table.ts'
import { IconX } from '@tabler/icons-react'
import { Table } from '@tanstack/react-table'
import { ReactNode } from 'react'

import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'

import DataTableFacetedFilter from './DataTableFacetedFilter.tsx'
import DataTableViewOptions from './DataTableViewOptions'

interface DataTableToolbarProps<TData> {
    table: Table<TData>
    filterFields?: DataTableFilterField<TData>[]
    searchable?: boolean
    rightActions?: ReactNode
}

const DataTableToolbar = <TData,>({
    table,
    filterFields = [],
    searchable,
    rightActions,
}: DataTableToolbarProps<TData>) => {
    const isFiltered = table.getState().columnFilters.length > 0

    return (
        <div className='flex flex-wrap items-center gap-2'>
            <div className='flex min-w-0 flex-1 flex-wrap items-center gap-2'>
                {searchable && (
                    <Input
                        placeholder='Search...'
                        value={table.getState().globalFilter ?? ''}
                        onChange={e => table.setGlobalFilter(e.target.value)}
                        className='h-8 w-[150px] bg-background lg:w-[250px]'
                    />
                )}
                {filterFields.map(({ id, label, options }) => {
                    const column = table.getColumn(id)
                    if (!column) return null

                    return (
                        <DataTableFacetedFilter
                            key={id}
                            column={column}
                            title={label}
                            options={options ?? []}
                        />
                    )
                })}
                {isFiltered && (
                    <Button
                        variant='ghost'
                        onClick={() => table.resetColumnFilters()}
                        className='h-8 px-2 lg:px-3'
                    >
                        Reset
                        <IconX className={'size-4'} />
                    </Button>
                )}
            </div>
            <div className={'ml-auto flex items-center gap-2'}>
                <DataTableViewOptions table={table} />
                {rightActions}
            </div>
        </div>
    )
}

export default DataTableToolbar
