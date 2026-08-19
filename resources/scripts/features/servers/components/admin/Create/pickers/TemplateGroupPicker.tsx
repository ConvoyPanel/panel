import {
    getTemplateGroup,
    getTemplateGroups,
} from '@/features/template-groups/api.ts'
import { TemplateGroup } from '@/types/template-group'
import { cn } from '@/utils'
import { IconCheck, IconFolder } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { useController } from 'react-hook-form'

import { ResourceComboboxForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton'

const TemplateGroupPicker = () => {
    const { field } = useController<{
        templateGroupId: string
    }>({
        name: 'templateGroupId',
    })

    // We don't fetch the selected entity by id; just show skeleton or fallback label
    const { data: selected, isLoading: isLoadingSelection } = useQuery({
        queryKey: ['template-group', field.value],
        queryFn: () => getTemplateGroup(field.value),
        enabled: !!field.value,
    })
    const selectedName = selected?.name

    return (
        <ResourceComboboxForm<TemplateGroup>
            queryKey={'template-groups'}
            accessorKey={'uuid'}
            name={'templateGroupId'}
            fetcher={async (query, page) => {
                const items = await getTemplateGroups({
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
                        'Select a template group'
                    )}

                    <IconFolder className={'ml-auto size-4 opacity-50'} />
                </>
            )}
            label={'Template Group'}
            searchPlaceholder={'Search template groups...'}
        />
    )
}

export default TemplateGroupPicker
