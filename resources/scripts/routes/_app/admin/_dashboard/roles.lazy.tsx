import { useUser } from '@/features/auth/api.ts'
import {
    deleteAdminRole,
    roleQueries,
    useAdminRoles,
} from '@/features/roles/api.ts'
import RoleFormDialog, {
    type RoleDialogTarget,
} from '@/features/roles/components/RoleFormDialog.tsx'
import {
    type AdminRole,
    adminPermissionLabels,
} from '@/features/roles/types.ts'
import { IconPlus, IconShieldLock } from '@tabler/icons-react'
import { useMutation } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import useConfirmationStore from '@/components/ui/AlertDialog/use-confirmation-store.ts'
import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import { Card, CardContent } from '@/components/ui/Card'
import { CardTable, type CardTableColumn } from '@/components/ui/CardTable'
import { DropdownMenuItem } from '@/components/ui/DropdownMenu'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import PageToolbar from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import Actions from '@/components/ui/Table/Actions.tsx'
import { toast } from '@/components/ui/Toast'
import { Heading } from '@/components/ui/Typography'

const countOf = (count: number, noun: string) =>
    `${count} ${noun}${count === 1 ? '' : 's'}`

/** Two named permissions and a count. The dialog is where the full list lives. */
const summarize = (role: AdminRole): string => {
    if (role.isSuperadmin) return 'Everything'
    if (role.permissions.length === 0) return 'Nothing'

    const [first, second, ...rest] = role.permissions
    const named = [first, second]
        .filter(Boolean)
        .map(permission => adminPermissionLabels[permission!])
        .join(', ')

    return rest.length > 0 ? `${named} and ${rest.length} more` : named
}

const Roles = () => {
    const { data: roles, isLoading } = useAdminRoles()
    const { data: currentUser } = useUser()
    const [target, setTarget] = useState<RoleDialogTarget | null>(null)
    const confirm = useConfirmationStore(state => state.confirm)

    // Authoring a role is a full administrator's act: three of the permissions are each a path
    // back to full control. Assigning an existing one only needs `users.manage`.
    const canAuthor = currentUser?.rootAdmin === true

    const { mutate: remove } = useMutation({
        mutationFn: (role: AdminRole) => deleteAdminRole(role.uuid),
        onSuccess: async () => {
            toast.add({ title: 'Role deleted', type: 'success' })
            await queryClient.invalidateQueries({ queryKey: roleQueries.all() })
        },
        onError: () =>
            toast.add({ title: 'Failed to delete role', type: 'error' }),
    })

    // Delete is always offered and the dialog is where it is refused, with the reason attached --
    // the same shape the users list uses.
    const confirmAndDelete = async (role: AdminRole) => {
        const held = role.usersCount ?? 0
        const blocked = role.isSystem || held > 0

        const confirmed = await confirm({
            title: role.isSystem
                ? 'Built-in role'
                : held > 0
                  ? `${role.name} is in use`
                  : 'Delete role',
            description: role.isSystem
                ? 'A built-in role cannot be deleted. Duplicate it if you need a variation.'
                : held > 0
                  ? `${countOf(held, 'account')} still hold this role. Move them to another one first.`
                  : `Delete ${role.name}? This cannot be undone.`,
            confirmText: 'Delete',
            cancelText: blocked ? 'Close' : 'Cancel',
            confirmButton: { variant: 'destructive', disabled: blocked },
        })

        if (confirmed) remove(role)
    }

    const columns: CardTableColumn<AdminRole>[] = [
        {
            key: 'name',
            header: 'Role',
            fill: true,
            cell: role => (
                <div className={'flex min-w-0 flex-col'}>
                    <div className={'flex items-center gap-2'}>
                        <span className={'truncate font-medium'}>
                            {role.name}
                        </span>
                        {role.isSystem && (
                            <Badge variant={'outline'}>Built in</Badge>
                        )}
                    </div>
                    {role.description && (
                        <p className={'text-muted-foreground truncate text-xs'}>
                            {role.description}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: 'permissions',
            header: 'Grants',
            // Nowrap rather than a width: the Role column fills, and under
            // `table-layout: auto` its `width: 100%` beats a width declared
            // here, so `w-[22rem]` computes to the same starved 94px. Refusing
            // to wrap makes this column's min-content width the thing the fill
            // column has to yield to. The summary is already bounded ("... and
            // N more"), and the card scrolls sideways if it ever is not.
            className: 'whitespace-nowrap',
            cell: role => (
                <span className={'text-muted-foreground text-sm'}>
                    {summarize(role)}
                </span>
            ),
        },
        {
            key: 'users',
            header: 'Accounts',
            align: 'right',
            cell: role => role.usersCount ?? 0,
        },
        {
            key: 'actions',
            align: 'right',
            cell: role => (
                <Actions
                    disabledReason={
                        canAuthor
                            ? undefined
                            : 'Only a full administrator can change roles.'
                    }
                >
                    <DropdownMenuItem
                        onClick={() => setTarget({ mode: 'edit', role })}
                    >
                        Edit
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        onClick={() => setTarget({ mode: 'duplicate', role })}
                    >
                        Duplicate
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        variant={'destructive'}
                        onClick={() => confirmAndDelete(role)}
                    >
                        Delete
                    </DropdownMenuItem>
                </Actions>
            ),
        },
    ]

    const addButton = canAuthor ? (
        <Button onClick={() => setTarget({ mode: 'new' })}>
            <IconPlus className={'size-4'} /> Add role
        </Button>
    ) : null

    return (
        <>
            <Heading>Roles</Heading>
            <PageToolbar actions={addButton} />
            {isLoading ? (
                <Skeleton className={'h-64'} />
            ) : (
                <Card>
                    <CardContent className={'p-0'}>
                        <CardTable
                            caption={'Admin roles'}
                            rows={roles ?? []}
                            rowKey={role => role.uuid}
                            columns={columns}
                            empty={
                                <SimpleEmptyState
                                    icon={IconShieldLock}
                                    title={'No roles'}
                                    description={'Add a role.'}
                                    action={addButton ?? undefined}
                                />
                            }
                        />
                    </CardContent>
                </Card>
            )}

            <RoleFormDialog target={target} close={() => setTarget(null)} />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/admin/_dashboard/roles')({
    component: Roles,
})
