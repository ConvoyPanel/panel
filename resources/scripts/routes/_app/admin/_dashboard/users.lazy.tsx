import { useUser as useCurrentUser } from '@/features/auth/api.ts'
import { deleteUser, userQueries, useUsers } from '@/features/users/api.ts'
import UserFormDialog from '@/features/users/components/UserFormDialog.tsx'
import useDataTable from '@/hooks/use-data-table.ts'
import type { AdminUser } from '@/types/admin/user.ts'
import { IconPlus, IconUsers } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { format } from 'date-fns'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import DataTableColumnHeader from '@/components/ui/DataTable/DataTableColumnHeader.tsx'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'
import Actions, { actionsColumn } from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

const RoleBadge = ({ user }: { user: AdminUser }) =>
    user.rootAdmin ? (
        <Badge variant={'secondary'}>Administrator</Badge>
    ) : (
        <Badge variant={'outline'}>User</Badge>
    )

const UsersIndex = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const { data: currentUser } = useCurrentUser()
    const [editing, setEditing] = useState<AdminUser | 'new' | null>(null)

    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } = useUsers(queryParams)

    // The list is sorted and paged server-side and a delete changes the counts
    // on rows this page cannot see, so this refetches rather than patching the
    // cached page in place.
    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: userQueries.all() })

    const { mutate: remove } = useMutation({
        mutationFn: (user: AdminUser) => deleteUser(user.id),
        onSuccess: async () => {
            toast.add({ title: 'User deleted', type: 'success' })
            await refresh()
        },
        onError: () =>
            toast.add({ title: 'Failed to delete user', type: 'error' }),
    })

    /*
     * Delete is always offered, and the confirmation is where it is refused —
     * the same shape the anchors list uses. Hiding the item an operator came for
     * leaves them to work out why it is missing; asking and then answering
     * "because four servers belong to them" is the same refusal with the reason
     * attached.
     */
    const handleDelete = async (user: AdminUser) => {
        const isSelf = user.id === currentUser?.id
        const owns = user.serversCount > 0

        const confirmed = await confirm({
            title: isSelf
                ? 'You are signed in as this account'
                : owns
                  ? `${user.name} still owns servers`
                  : 'Delete user',
            description: isSelf
                ? 'An account cannot delete itself. Another administrator can remove it for you.'
                : owns
                  ? `${countOf(user.serversCount, 'server')} still belong to this account. Transfer or delete ${user.serversCount === 1 ? 'it' : 'them'} first.`
                  : `Delete ${user.name} (${user.email})? Their API tokens, SSH keys and sessions go with them. This cannot be undone.`,
            confirmText: 'Delete',
            cancelText: isSelf || owns ? 'Close' : 'Cancel',
            confirmButton: {
                variant: 'destructive',
                disabled: isSelf || owns,
            },
        })

        if (confirmed) remove(user)
    }

    const renderActions = (user: AdminUser) => (
        <>
            <DropdownMenuItem onClick={() => setEditing(user)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => handleDelete(user)}
            >
                Delete
            </DropdownMenuItem>
        </>
    )

    const columns: ColumnDef<AdminUser>[] = [
        {
            accessorKey: 'name',
            enableHiding: false,
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={'Name'} />
            ),
            meta: { skeletonWidth: '8rem' },
        },
        {
            accessorKey: 'email',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={'Email'} />
            ),
            meta: { skeletonWidth: '12rem' },
        },
        {
            id: 'rootAdmin',
            accessorKey: 'rootAdmin',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={'Role'} />
            ),
            meta: { skeletonWidth: '6rem' },
            cell: ({ row }) => <RoleBadge user={row.original} />,
        },
        {
            id: 'serversCount',
            accessorKey: 'serversCount',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={'Servers'} />
            ),
            meta: { skeletonWidth: '2rem' },
            cell: ({ cell }) => cell.getValue<number>() ?? 0,
        },
        {
            id: 'createdAt',
            accessorKey: 'createdAt',
            enableSorting: true,
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title={'Created'} />
            ),
            meta: { skeletonWidth: '6rem' },
            cell: ({ cell }) => {
                const value = cell.getValue<string | null>()

                return value ? (
                    <span className={'whitespace-nowrap'}>
                        {format(new Date(value), 'PP')}
                    </span>
                ) : (
                    <span className={'text-muted-foreground'}>—</span>
                )
            },
        },
        actionsColumn<AdminUser>(({ row }) => renderActions(row.original)),
    ]

    const addButton = (
        <Button onClick={() => setEditing('new')}>
            <IconPlus className={'size-4'} />
            Add user
        </Button>
    )

    return (
        <>
            <Heading>Users</Heading>
            <DataTable
                paginated
                searchable
                toolbar
                data={data}
                columns={columns}
                isPlaceholderData={isPlaceholderData}
                isError={isError}
                onRetry={refetch}
                rightActions={addButton}
                emptyState={
                    <SimpleEmptyState
                        icon={IconUsers}
                        title={'No users'}
                        description={
                            'Everyone who can sign in to the panel appears here.'
                        }
                        action={addButton}
                    />
                }
                mobileRow={row => {
                    const user = row.original

                    return (
                        <Item variant={'muted'} size={'sm'}>
                            <ItemContent className={'min-w-0'}>
                                <ItemTitle
                                    className={'w-full min-w-0 gap-2'}
                                >
                                    <span className={'truncate'}>
                                        {user.name}
                                    </span>
                                    <RoleBadge user={user} />
                                </ItemTitle>
                                <ItemDescription
                                    className={'block truncate text-nowrap'}
                                >
                                    {user.email}
                                </ItemDescription>
                                <ItemDescription>
                                    {countOf(user.serversCount ?? 0, 'server')}
                                </ItemDescription>
                            </ItemContent>
                            <ItemActions>
                                <Actions>{renderActions(user)}</Actions>
                            </ItemActions>
                        </Item>
                    )
                }}
                {...tableProps}
            />

            <UserFormDialog
                user={editing}
                currentUserId={currentUser?.id}
                close={() => setEditing(null)}
                refresh={refresh}
            />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/admin/_dashboard/users')({
    component: UsersIndex,
})
