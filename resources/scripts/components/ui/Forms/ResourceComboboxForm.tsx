import { cn } from '@/utils'
import { PaginatedResult } from '@/utils/http.ts'
import { useDebouncedValue } from '@mantine/hooks'
import { useInfiniteQuery } from '@tanstack/react-query'
import { CommandLoading } from 'cmdk'
import { ReactNode, useId, useState } from 'react'

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
    FormControl,
    FormDescription,
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
    queryKey: string
    accessorKey: keyof T
    name: string
    fetcher: (query: string, page: number) => Promise<PaginatedResult<T>>
    renderItem: (item: T, isSelected: boolean) => ReactNode
    renderTrigger: () => ReactNode
    label: string
    searchPlaceholder?: string
    nothingFoundMessage?: ReactNode
    description?: ReactNode
}

const CommandSpinner = () => {
    return (
        <CommandLoading className='grid place-items-center py-4'>
            <Spinner className='size-6' />
        </CommandLoading>
    )
}

const ResourceComboboxForm = <T,>({
    queryKey,
    accessorKey,
    name,
    fetcher,
    renderItem,
    renderTrigger,
    label,
    searchPlaceholder,
    nothingFoundMessage,
    description,
}: ResourceComboBoxFormProps<T>) => {
    const [open, setOpen] = useState(false)
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 300)
    const listId = useId()

    const {
        data,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage,
        isLoading: isLoadingResults,
    } = useInfiniteQuery<PaginatedResult<T>>({
        queryKey: [queryKey, debouncedQuery],
        queryFn: ({ pageParam = 1 }) =>
            fetcher(debouncedQuery, pageParam as number),
        initialPageParam: 1,
        getNextPageParam: (lastPage, allPages) => {
            if (lastPage.items.length < lastPage.pagination.perPage) {
                return undefined
            }
            return allPages.length + 1
        },
    })

    const pages = data?.pages ?? []
    const isEmpty = pages[0]?.items.length === 0

    return (
        <FormField
            name={name}
            render={({ field, formState }) => (
                <FormItem>
                    <FormLabel>{label}</FormLabel>
                    <Popover open={open} onOpenChange={setOpen}>
                        <FormControl>
                            <PopoverTrigger asChild>
                                <Button
                                    type={'button'}
                                    variant={'outline'}
                                    role={'combobox'}
                                    aria-expanded={open}
                                    aria-controls={listId}
                                    aria-autocomplete={'list'}
                                    disabled={formState.isSubmitting}
                                    className={cn(
                                        'w-full justify-between text-left font-normal',
                                        !field.value && 'text-muted-foreground'
                                    )}
                                >
                                    {renderTrigger()}
                                </Button>
                            </PopoverTrigger>
                        </FormControl>
                        <PopoverContent id={listId} className='w-md p-0'>
                            <Command shouldFilter={false}>
                                <CommandInput
                                    value={query}
                                    onValueChange={setQuery}
                                    placeholder={
                                        searchPlaceholder ?? 'Search...'
                                    }
                                    aria-label={
                                        searchPlaceholder ?? `Search ${label}`
                                    }
                                />
                                <CommandList>
                                    <ScrollArea
                                        onBottomReached={() => {
                                            if (
                                                hasNextPage &&
                                                !isFetchingNextPage
                                            ) {
                                                fetchNextPage()
                                            }
                                        }}
                                        className='h-[300px]'
                                    >
                                        {isLoadingResults ? (
                                            <CommandSpinner />
                                        ) : (
                                            isEmpty && (
                                                <CommandEmpty>
                                                    {nothingFoundMessage ??
                                                        'No results found'}
                                                </CommandEmpty>
                                            )
                                        )}
                                        <CommandGroup>
                                            {pages.map(pageData =>
                                                pageData.items.map(
                                                    (item: T) => {
                                                        const rawVal = (
                                                            item as any
                                                        )[accessorKey]
                                                        if (
                                                            rawVal ===
                                                                undefined ||
                                                            rawVal === null
                                                        )
                                                            return null
                                                        const strVal =
                                                            String(rawVal)
                                                        return (
                                                            <CommandItem
                                                                key={strVal}
                                                                value={strVal}
                                                                onSelect={val => {
                                                                    field.onChange(
                                                                        val
                                                                    )
                                                                    setOpen(
                                                                        false
                                                                    )
                                                                }}
                                                            >
                                                                {renderItem(
                                                                    item,
                                                                    field.value ===
                                                                        String(
                                                                            (
                                                                                item as any
                                                                            )[
                                                                                accessorKey
                                                                            ]
                                                                        )
                                                                )}
                                                            </CommandItem>
                                                        )
                                                    }
                                                )
                                            )}
                                        </CommandGroup>
                                        {isFetchingNextPage && (
                                            <CommandSpinner />
                                        )}
                                    </ScrollArea>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    {description && (
                        <FormDescription>{description}</FormDescription>
                    )}
                    <FormMessage />
                </FormItem>
            )}
        />
    )
}

export default ResourceComboboxForm
