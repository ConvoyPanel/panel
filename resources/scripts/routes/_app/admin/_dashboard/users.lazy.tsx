import { useUser as useCurrentUser } from '@/features/auth/api.ts'
import { userQueries, useUsers } from '@/features/users/api.ts'
import UserFormDialog from '@/features/users/components/UserFormDialog.tsx'
import useUserDeletion from '@/features/users/hooks/use-user-deletion.ts'
import useDataTable from '@/hooks/use-data-table.ts'
import type { AdminUser } from '@/types/admin/user.ts'
import { cn } from '@/utils'
import { IconPlus, IconUsers } from '@tabler/icons-react'
import { Link, createLazyFileRoute } from '@tanstack/react-router'
import { ColumnDef } from '@tanstack/react-table'
import { format } from 'date-fns'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import { Badge } from '@/components/ui/Badge.tsx'
import { Button, buttonVariants } from '@/components/ui/Button'
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
    const { data: currentUser } = useCurrentUser()
    const [editing, setEditing] = useState<AdminUser | 'new' | null>(null)

    const { queryParams, tableProps } = useDataTable()
    const { data, isPlaceholderData, isError, refetch } = useUsers(queryParams)

    // The list is sorted and paged server-side and a delete changes the counts
    // on rows this page cannot see, so this refetches rather than patching the
    // cached page in place.
    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: userQueries.all() })

    // Shared with the detail page's header, so the two cannot refuse differently.
    const { confirmAndDelete } = useUserDeletion()

    const renderActions = (user: AdminUser) => (
        <>
            <DropdownMenuItem asChild>
                <Link to={`/admin/users/${user.id}` as string}>Open</Link>
            </DropdownMenuItem>
            <DropdownMenuItem onClick={() => setEditing(user)}>
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem
                variant={'destructive'}
                onClick={() => confirmAndDelete(user)}
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
            cell: ({ cell }) => (
                <Link
                    className={cn(buttonVariants({ variant: 'link' }), 'px-0')}
                    to={`/admin/users/${cell.row.original.id}` as string}
                >
                    {cell.getValue<string>()}
                </Link>
            ),
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
                                    <Link
                                        className={'truncate'}
                                        to={`/admin/users/${user.id}` as string}
                                    >
                                        {user.name}
                                    </Link>
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
