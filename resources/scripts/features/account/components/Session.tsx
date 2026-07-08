import { IconDeviceLaptop } from '@tabler/icons-react'
import { formatDistanceToNow } from 'date-fns'
import { useMemo } from 'react'

import { Session as SessionType } from '@/features/account/sessions/api.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'

interface Props {
    session: SessionType
    onRevoke: (session: SessionType) => void
}

const Session = ({ session, onRevoke }: Props) => {
    const lastActive = useMemo(
        () =>
            formatDistanceToNow(session.lastActiveAt, { addSuffix: true }),
        [session.lastActiveAt]
    )

    return (
        <div className={'flex items-center py-2 pr-2'}>
            <IconDeviceLaptop className={'mt-px mr-4 shrink-0'} />
            <div className={'space-y-1 overflow-x-hidden'}>
                <div className={'flex items-center gap-2'}>
                    <p className={'truncate text-sm font-medium leading-none'}>
                        {session.device}
                    </p>
                    {session.isCurrent && (
                        <Badge variant={'secondary'}>This device</Badge>
                    )}
                </div>
                <p className={'text-xs text-muted-foreground'}>
                    {session.ipAddress ?? 'Unknown IP'} · active {lastActive}
                </p>
            </div>
            <div className={'min-w-[1rem] grow'} />
            {!session.isCurrent && (
                <Button
                    variant={'ghost'}
                    size={'sm'}
                    className={'shrink-0'}
                    onClick={() => onRevoke(session)}
                >
                    Revoke
                </Button>
            )}
        </div>
    )
}

export default Session
