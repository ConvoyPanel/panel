import { useUser } from '@/features/users/api.ts'
import { userQueries } from '@/features/users/api.ts'
import AccountCard from '@/features/users/components/Overview/AccountCard.tsx'
import UserActivityCard from '@/features/users/components/Overview/UserActivityCard.tsx'
import UserResourceCards from '@/features/users/components/Overview/UserResourceCards.tsx'
import UserServersCard from '@/features/users/components/Overview/UserServersCard.tsx'
import UserFormDialog from '@/features/users/components/UserFormDialog.tsx'
import useUserDeletion from '@/features/users/hooks/use-user-deletion.ts'
import { createLazyFileRoute, useNavigate } from '@tanstack/react-router'
import { useState } from 'react'

import { queryClient } from '@/lib/query-client.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { PageToolbar } from '@/components/ui/PageToolbar'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/admin/users/$userId/')({
    component: UserOverview,
})

function UserOverview() {
    const { userId } = Route.useParams()
    const numericUserId = Number(userId)
    const navigate = useNavigate()
    const [editing, setEditing] = useState(false)

    const { data: user } = useUser(numericUserId)
    const { confirmAndDelete, currentUserId } = useUserDeletion({
        // The page this was started from is about to 404, so leave for the list.
        onDeleted: () => void navigate({ to: '/admin/users' }),
    })

    return (
        <>
            <div className={'flex min-w-0 flex-col gap-1'}>
                <div className={'flex flex-wrap items-center gap-2'}>
                    {user ? (
                        <Heading>{user.name}</Heading>
                    ) : (
                        <Skeleton className={'h-9 w-48'} />
                    )}
                    {user && (
                        <>
                            <Badge
                                variant={
                                    user.rootAdmin ? 'secondary' : 'outline'
                                }
                            >
                                {user.rootAdmin ? 'Administrator' : 'User'}
                            </Badge>
                            {user.twoFactorEnabled && (
                                <Badge variant={'outline'}>Two-factor on</Badge>
                            )}
                        </>
                    )}
                </div>
                <p className={'text-muted-foreground truncate'}>
                    {user?.email}
                </p>
            </div>

            <PageToolbar
                actions={
                    <>
                        <Button
                            variant={'destructive'}
                            disabled={!user}
                            onClick={() => user && confirmAndDelete(user)}
                        >
                            Delete
                        </Button>
                        <Button
                            disabled={!user}
                            onClick={() => setEditing(true)}
                        >
                            Edit user
                        </Button>
                    </>
                }
            />

            <UserResourceCards resources={user?.resources} />

            <div className={'grid grid-cols-1 gap-4 @3xl:grid-cols-2'}>
                <AccountCard user={user} />
                <UserActivityCard userId={numericUserId} />
            </div>

            <UserServersCard userId={numericUserId} />

            <UserFormDialog
                user={editing && user ? user : null}
                currentUserId={currentUserId}
                close={() => setEditing(false)}
                refresh={() =>
                    queryClient.invalidateQueries({
                        queryKey: userQueries.all(),
                    })
                }
            />
        </>
    )
}
