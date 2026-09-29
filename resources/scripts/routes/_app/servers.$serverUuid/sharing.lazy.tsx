import {
    removeSubuser,
    subuserQueries,
    useSubusers,
} from '@/features/subusers/api.ts'
import EditSubuserDialog from '@/features/subusers/components/EditSubuserDialog.tsx'
import ShareServerDialog from '@/features/subusers/components/ShareServerDialog.tsx'
import { type Subuser, permissionLabels } from '@/features/subusers/types.ts'
import { IconUsers } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
import { Card, CardContent } from '@/components/ui/Card'
import { CardTable, type CardTableColumn } from '@/components/ui/CardTable'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import PageToolbar from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import Actions from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'
import UserAvatar from '@/components/ui/UserAvatar.tsx'

/**
 * A grant is up to twenty permissions, and a row is one line. Two names and a count is what fits
 * and what answers "roughly how much can they do?"; the dialog is where the full list lives.
 */
const summarize = (subuser: Subuser): string => {
    if (subuser.permissions.length === 0) return 'No access yet'

    const [first, second, ...rest] = subuser.permissions

    const named = [first, second]
        .filter(Boolean)
        .map(permission => permissionLabels[permission!])
        .join(', ')

    return rest.length > 0 ? `${named} and ${rest.length} more` : named
}

const Sharing = () => {
    const { serverUuid } = Route.useParams()
    const { data: subusers, isLoading } = useSubusers(serverUuid)
    const [editing, setEditing] = useState<Subuser | null>(null)
    const confirm = useConfirmationStore(state => state.confirm)

    const { mutate: remove } = useMutation({
        mutationFn: (subuser: Subuser) =>
            removeSubuser(serverUuid, subuser.uuid),
        onSuccess: async () => {
            toast.add({ title: 'Access removed', type: 'success' })
            await queryClient.invalidateQueries({
                queryKey: subuserQueries.all(serverUuid),
            })
        },
        onError: () =>
            toast.add({ title: 'Failed to remove access', type: 'error' }),
    })

    const confirmAndRemove = async (subuser: Subuser) => {
        const confirmed = await confirm({
            title: 'Remove access',
            description: `${subuser.name} (${subuser.email}) loses access to this server immediately.`,
            confirmText: 'Remove',
            confirmButton: { variant: 'destructive' },
        })

        if (confirmed) remove(subuser)
    }

    const columns: CardTableColumn<Subuser>[] = [
        {
            key: 'name',
            header: 'Person',
            fill: true,
            cell: subuser => (
                <div className={'flex min-w-0 items-center gap-3'}>
                    <UserAvatar
                        name={subuser.name}
                        src={subuser.avatarUrl}
                        className={'size-8'}
                    />
                    <div className={'min-w-0'}>
                        <div className={'flex items-center gap-2'}>
                            <span className={'truncate font-medium'}>
                                {subuser.name}
                            </span>
                            {subuser.isPending && (
                                <Badge variant={'outline'}>Invited</Badge>
                            )}
                        </div>
                        <p className={'text-muted-foreground truncate text-xs'}>
                            {subuser.email}
                        </p>
                    </div>
                </div>
            ),
        },
        {
            key: 'permissions',
            header: 'Can do',
            cell: subuser => (
                <span className={'text-muted-foreground text-sm'}>
                    {summarize(subuser)}
                </span>
            ),
        },
        {
            key: 'actions',
            align: 'right',
            cell: subuser => (
                <Actions>
                    <DropdownMenuItem onClick={() => setEditing(subuser)}>
                        Edit access
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant={'destructive'}
                        onClick={() => confirmAndRemove(subuser)}
                    >
                        Remove
                    </DropdownMenuItem>
                </Actions>
            ),
        },
    ]

    return (
        <>
            <Heading>Sharing</Heading>
            <PageToolbar actions={<ShareServerDialog server={serverUuid} />} />
            {isLoading ? (
                <Skeleton className={'h-48'} />
            ) : (
                <Card>
                    <CardContent className={'p-0'}>
                        <CardTable
                            caption={'People this server is shared with'}
                            rows={subusers ?? []}
                            rowKey={subuser => subuser.uuid}
                            columns={columns}
                            empty={
                                <SimpleEmptyState
                                    icon={IconUsers}
                                    title={'Not shared'}
                                    description={
                                        'Share this server with someone.'
                                    }
                                />
                            }
                        />
                    </CardContent>
                </Card>
            )}

            <EditSubuserDialog
                server={serverUuid}
                subuser={editing}
                close={() => setEditing(null)}
            />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/sharing')({
    component: Sharing,
})
