import { cn } from '@/utils'
import { PaginatedResult } from '@/utils/http.ts'
import { useDebouncedValue } from '@mantine/hooks'
import { CommandLoading } from 'cmdk'
import { ReactNode, useState } from 'react'
import useSWRInfinite from 'swr/infinite'

import { Button } from '@/components/ui/Button'
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/Command'
import {
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/Form'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/Popover'
import { ScrollArea } from '@/components/ui/ScrollArea'
import Spinner from '@/components/ui/Spinner.tsx'

export interface ResourceComboBoxFormProps<T> {
    swrKey: string
    accessorKey: keyof T
    name: string
    fetcher: (query: string, page: number) => Promise<PaginatedResult<T>>
    renderItem: (item: T, isSelected: boolean) => ReactNode
    renderTrigger: () => ReactNode
    label: string
    searchPlaceholder?: string
    nothingFoundMessage?: ReactNode
}

const getKey = <T,>(
    prefix: string,
    query: string,
    pageIndex: number,
    previousPageData: PaginatedResult<T> | null
) => {
    // If previous page exists and item count is less than perPage, we've reached the end.
    if (
        previousPageData &&
        previousPageData.items.length < previousPageData.pagination.perPage
    ) {
        return null
    }
    return [prefix, query, pageIndex]
}

const CommandSpinner = () => {
    return (
        <CommandLoading className='grid place-items-center py-4'>
            <Spinner className='size-6' />
        </CommandLoading>
    )
}

const ResourceComboboxForm = <T,>({
    swrKey,
    accessorKey,
    name,
    fetcher,
    renderItem,
    renderTrigger,
    label,
    searchPlaceholder,
    nothingFoundMessage,
}: ResourceComboBoxFormProps<T>) => {
    const [open, setOpen] = useState(false)
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 300)

    const {
        data,
        size,
        setSize,
        isLoading: isLoadingResults,
    } = useSWRInfinite(
        (idx, prev) => getKey<T>(swrKey, debouncedQuery, idx + 1, prev),
        ([_, query, page]) => fetcher(query as string, page as number)
    )

    const isEmpty = data?.[0]?.items.length === 0
    const isReachingEnd =
        isEmpty ||
        (data &&
            data[data.length - 1]?.items.length <
                data[data.length - 1]?.pagination.perPage)
    const isLoadingMore =
        isLoadingResults ||
        (size > 0 && data && typeof data[size - 1] === 'undefined')


    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <Popover open={open} onOpenChange={setOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                variant='outline'
                                className={cn(
                                    'w-full text-left font-normal',
                                    !field.value && 'text-muted-foreground'
                                )}
                                onClick={() => setOpen(true)}
                            >
                                {renderTrigger()}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className='w-md p-0'>
                            <Command shouldFilter={false}>
                                <CommandInput
                                    value={query}
                                    onValueChange={setQuery}
                                    placeholder={searchPlaceholder ?? 'Search...'}
                                    className='h-9'
                                />
                                <CommandList>
                                    <ScrollArea
                                        onBottomReached={() =>
                                            setSize(size + 1)
                                        }
                                        className='h-[300px]'
                                    >
                                        {isLoadingResults ? (
                                            <CommandSpinner />
                                        ) : (
                                            <CommandEmpty>
                                                {nothingFoundMessage ??
                                                    'No results found'}
                                            </CommandEmpty>
                                        )}
                                        <CommandGroup>
                                            {data &&
                                                data.map(pageData =>
                                                    pageData.items.map(item => {
                                                        const rawVal = (item as any)[
                                                            accessorKey
                                                        ]
                                                        if (
                                                            rawVal === undefined ||
                                                            rawVal === null
                                                        )
                                                            return null
                                                        const strVal = String(
                                                            rawVal
                                                        )
                                                        return (
                                                            <CommandItem
                                                                key={strVal}
                                                                value={strVal}
                                                                onSelect={val => {
                                                                    field.onChange(
                                                                        val
                                                                    )
                                                                    setOpen(false)
                                                                }}
                                                            >
                                                                {renderItem(
                                                                    item,
                                                                    field.value ===
                                                                        String(
                                                                            (item as any)[
                                                                                accessorKey
                                                                            ]
                                                                        )
                                                                )}
                                                            </CommandItem>
                                                        )
                                                    })
                                                )}
                                        </CommandGroup>
                                        {!isReachingEnd && isLoadingMore && (
                                            <CommandSpinner />
                                        )}
                                    </ScrollArea>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default ResourceComboboxForm
