import {
    getImageGroup,
    getImageGroups,
} from '@/features/images/api.ts'
import { ImageGroup } from '@/types/image'
import { cn } from '@/utils'
import { IconCheck, IconFolder } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { useController } from 'react-hook-form'

import { ResourceComboboxForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton'

const ImageGroupPicker = () => {
    const { field } = useController<{
        imageGroupId: string
    }>({
        name: 'imageGroupId',
    })

    // We don't fetch the selected entity by id; just show skeleton or fallback label
    const { data: selected, isLoading: isLoadingSelection } = useQuery({
        queryKey: ['image-group', field.value],
        queryFn: () => getImageGroup(field.value),
        enabled: !!field.value,
    })
    const selectedName = selected?.name

    return (
        <ResourceComboboxForm<ImageGroup>
            queryKey={'image-groups'}
            accessorKey={'uuid'}
            name={'imageGroupId'}
            fetcher={async (query, page) => {
                const items = await getImageGroups({
                    page,
                    filters: {
                        name: query,
                    },
                })
                return {
                    items,
                    pagination: {
                        total: items.length,
                        count: items.length,
                        perPage: items.length || 50,
                        currentPage: 1,
                        totalPages: 1,
                    },
                }
            }}
            renderItem={(item, isSelected) => (
                <>
                    <dl className={'flex grow flex-col overflow-hidden'}>
                        <dt className={'truncate'}>{item.name}</dt>
                    </dl>

                    <IconCheck
                        className={cn(
                            'shrink-0',
                            isSelected ? 'opacity-100' : 'opacity-0'
                        )}
                    />
                </>
            )}
            renderTrigger={() => (
                <>
                    {isLoadingSelection ? (
                        <Skeleton className={'h-3 w-24'} />
                    ) : selectedName ? (
                        selectedName
                    ) : (
                        'Select an operating system'
                    )}

                    <IconFolder className={'ml-auto size-4 opacity-50'} />
                </>
            )}
            label={'Operating system'}
            searchPlaceholder={'Search image groups...'}
        />
    )
}

export default ImageGroupPicker
