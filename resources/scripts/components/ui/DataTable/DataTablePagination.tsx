import {
    IconChevronLeft,
    IconChevronRight,
    IconChevronsLeft,
    IconChevronsRight,
} from '@tabler/icons-react'
import { Table } from '@tanstack/react-table'

import { Button } from '@/components/ui/Button'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/Select'

interface DataTablePaginationProps<TData> {
    table: Table<TData>
    perPageOptions?: number[]
    showPerPageOptions?: boolean
}

const DataTablePagination = <TData,>({
    table,
    perPageOptions = [10, 20, 30, 40, 50],
    showPerPageOptions,
}: DataTablePaginationProps<TData>) => {
    const perPageItems = perPageOptions.map(pageSize => ({
        value: `${pageSize}`,
        label: pageSize,
    }))

    return (
        <div className='flex items-center justify-between px-2'>
            <div className='flex-1' />
            <div className='flex items-center space-x-6 lg:space-x-8'>
                {showPerPageOptions && (
                    <div className='flex items-center space-x-2'>
                        <p className='text-sm font-medium'>Rows per page</p>
                        <Select
                            items={perPageItems}
                            value={`${table.getState().pagination.pageSize}`}
                            onValueChange={value => {
                                if (!value) return
                                table.setPageSize(Number(value))
                            }}
                        >
                            <SelectTrigger className='w-[70px]'>
                                <SelectValue
                                    placeholder={
                                        table.getState().pagination.pageSize
                                    }
                                />
                            </SelectTrigger>
                            <SelectContent
                                side='top'
                                alignItemWithTrigger={false}
                            >
                                {perPageItems.map(item => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
                <div className='flex w-[100px] items-center justify-center text-sm font-medium'>
                    Page {table.getState().pagination.pageIndex + 1} of{' '}
                    {table.getPageCount()}
                </div>
                <div className='flex items-center space-x-2'>
                    <Button
                        variant='outline'
                        size='icon'
                        className='hidden lg:flex'
                        onClick={() => table.setPageIndex(0)}
                        disabled={!table.getCanPreviousPage()}
                    >
                        <span className='sr-only'>Go to first page</span>
                        <IconChevronsLeft className={'size-4'} />
                    </Button>
                    <Button
                        variant='outline'
                        size='icon'
                        onClick={() => table.previousPage()}
                        disabled={!table.getCanPreviousPage()}
                    >
                        <span className='sr-only'>Go to previous page</span>
                        <IconChevronLeft className={'size-4'} />
                    </Button>
                    <Button
                        variant='outline'
                        size='icon'
                        onClick={() => table.nextPage()}
                        disabled={!table.getCanNextPage()}
                    >
                        <span className='sr-only'>Go to next page</span>
                        <IconChevronRight className={'size-4'} />
                    </Button>
                    <Button
                        variant='outline'
                        size='icon'
                        className='hidden lg:flex'
                        onClick={() =>
                            table.setPageIndex(table.getPageCount() - 1)
                        }
                        disabled={!table.getCanNextPage()}
                    >
                        <span className='sr-only'>Go to last page</span>
                        <IconChevronsRight className={'size-4'} />
                    </Button>
                </div>
            </div>
        </div>
    )
}

export default DataTablePagination
