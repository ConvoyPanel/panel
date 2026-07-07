import { AdminUser } from '@/types/admin/user.ts'
import { cn } from '@/utils'
import { IconCheck, IconUser } from '@tabler/icons-react'
import { useController } from 'react-hook-form'

import { getUsers, useUser } from '@/features/users/api.ts'
import { ResourceComboboxForm } from '@/components/ui/Forms'
import Skeleton from '@/components/ui/Skeleton.tsx'

const UserPicker = () => {
    const { field } = useController<{
        userId: string
    }>({
        name: 'userId',
    })
    const { data: selected, isLoading: isLoadingSelection } = useUser(
        field.value ? Number(field.value) : null
    )

    return (
        <ResourceComboboxForm<AdminUser>
            queryKey={'users'}
            accessorKey={'id'}
            name={'userId'}
            fetcher={(query, page) =>
                getUsers({
                    page: page,
                    filters: {
                        '*': query,
                    },
                })
            }
            renderItem={(item, isSelected) => (
                <>
                    <dl className={'flex grow flex-col overflow-hidden'}>
                        <dt className={'truncate'}>{item.name}</dt>
                        <dd
                            className={'truncate text-xs text-muted-foreground'}
                        >
                            {item.email}
                        </dd>
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
                    ) : selected ? (
                        selected.name
                    ) : (
                        'Select a user'
                    )}

                    <IconUser className={'ml-auto size-4 opacity-50'} />
                </>
            )}
            label={'User'}
            searchPlaceholder={'Search users...'}
        />
    )
}

export default UserPicker
