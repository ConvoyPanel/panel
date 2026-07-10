import { useMutation } from '@tanstack/react-query'
import { IconDevices } from '@tabler/icons-react'
import { toast } from 'sonner'

import {
    revokeSession,
    sessionQueries,
    useSessions,
    type Session as SessionType,
} from '@/features/account/sessions/api.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { cn } from '@/utils'

import Session from '@/features/account/components/Session.tsx'

import { useConfirmationStore } from '@/components/ui/AlertDialog'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import Skeleton from '@/components/ui/Skeleton.tsx'

const SessionListCard = () => {
    const confirm = useConfirmationStore(state => state.confirm)
    const mutate = useQueryMutator<SessionType[]>(sessionQueries.all())

    const { data: sessions, isLoading } = useSessions()

    const { mutate: revoke } = useMutation({
        mutationFn: (session: SessionType) => revokeSession(session.id),
        onSuccess: (_, session) => {
            mutate(list => list?.filter(s => s.id !== session.id))
            toast.success('Session revoked')
        },
        onError: () => toast.error('Failed to revoke session'),
    })

    const handleRevoke = async (session: SessionType) => {
        const confirmed = await confirm({
            title: 'Revoke session',
            description: `The session on ${session.device} will be signed out immediately.`,
        })
        if (!confirmed) return

        revoke(session)
    }

    return (
        <Card className={'@md:col-span-2'}>
            <CardHeader>
                <CardTitle>Active Sessions</CardTitle>
                <CardDescription>
                    Devices signed in to your account. Revoke any you don’t
                    recognize.
                </CardDescription>
            </CardHeader>
            <CardContent
                className={cn(
                    'min-h-[14rem]',
                    (isLoading || sessions?.length === 0) &&
                        'grid place-items-center'
                )}
            >
                {isLoading || !sessions ? (
                    <Skeleton className={'h-40 w-full'} />
                ) : sessions.length > 0 ? (
                    <ItemGroup className={'gap-3'}>
                        {sessions.map(session => (
                            <Session
                                key={session.id}
                                session={session}
                                onRevoke={handleRevoke}
                            />
                        ))}
                    </ItemGroup>
                ) : (
                    <SimpleEmptyState
                        icon={IconDevices}
                        title={'No active sessions'}
                        description={'There are no other sessions to show.'}
                    />
                )}
            </CardContent>
        </Card>
    )
}

export default SessionListCard
