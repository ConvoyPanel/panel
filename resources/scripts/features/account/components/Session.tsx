import { IconDeviceLaptop } from '@tabler/icons-react'
import { formatDistanceToNow } from 'date-fns'
import { useMemo } from 'react'

import { Session as SessionType } from '@/features/account/sessions/api.ts'

import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'

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
        <Item variant={'muted'} size={'sm'}>
            <ItemMedia variant={'icon'}>
                <IconDeviceLaptop />
            </ItemMedia>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>
                    {session.device}
                    {session.isCurrent && (
                        <Badge variant={'secondary'}>This device</Badge>
                    )}
                </ItemTitle>
                <p className={'text-xs text-muted-foreground'}>
                    {session.ipAddress ?? 'Unknown IP'} · active {lastActive}
                </p>
            </ItemContent>
            {!session.isCurrent && (
                <ItemActions>
                    <Button
                        variant={'ghost'}
                        size={'sm'}
                        onClick={() => onRevoke(session)}
                    >
                        Revoke
                    </Button>
                </ItemActions>
            )}
        </Item>
    )
}

export default Session
