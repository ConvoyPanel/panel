import type { AuditEntry } from '@/features/audit/api.ts'
import { describeAuditEvent } from '@/features/audit/event-copy.ts'
import {
    IconRobot,
    IconShieldLock,
    IconUser,
    IconUserQuestion,
} from '@tabler/icons-react'
import { format, formatDistanceToNow } from 'date-fns'
import { useMemo } from 'react'

import { Badge } from '@/components/ui/Badge'
import {
    Item,
    ItemContent,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/Item'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/Tooltip'

/** The icon stands in for who acted, which is the first thing anyone scans a feed for. */
const ACTOR_ICON = {
    user: IconUser,
    staff: IconShieldLock,
    system: IconRobot,
    unknown: IconUserQuestion,
} as const satisfies Record<App.Enums.Audit.AuditActorType, unknown>

interface Props {
    entry: AuditEntry
    /** The global admin feed names what was acted on; a server's own feed already knows. */
    showSubject?: boolean
}

const AuditEntryRow = ({ entry, showSubject = false }: Props) => {
    const { verb, detail } = useMemo(
        () => describeAuditEvent(entry.event, entry.properties),
        [entry.event, entry.properties]
    )

    const relative = useMemo(
        () => formatDistanceToNow(entry.createdAt, { addSuffix: true }),
        [entry.createdAt]
    )

    const Icon = ACTOR_ICON[entry.actor.type] ?? IconUserQuestion

    return (
        <Item variant={'muted'} size={'sm'}>
            <ItemMedia variant={'icon'}>
                <Icon />
            </ItemMedia>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>
                    <span className={'font-medium'}>{entry.actor.label}</span>{' '}
                    <span className={'text-muted-foreground'}>{verb}</span>
                    {detail && (
                        <Badge variant={'secondary'} className={'ml-1'}>
                            {detail}
                        </Badge>
                    )}
                </ItemTitle>
                <p
                    className={
                        'flex flex-wrap items-center gap-x-1.5 text-xs text-muted-foreground'
                    }
                >
                    {/* Exact timestamp on hover: "3 days ago" is the readable form, but an
                        investigation needs the real one. */}
                    <TooltipProvider>
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <span className={'cursor-default'}>
                                    {relative}
                                </span>
                            </TooltipTrigger>
                            <TooltipContent>
                                {format(entry.createdAt, 'PPpp')}
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                    {showSubject && entry.subject && (
                        <>
                            <span aria-hidden>·</span>
                            <span className={'truncate'}>
                                {entry.subject.label ??
                                    `${entry.subject.type} #${entry.subject.id}`}
                            </span>
                        </>
                    )}
                    {entry.ip && (
                        <>
                            <span aria-hidden>·</span>
                            <span>{entry.ip}</span>
                        </>
                    )}
                </p>
            </ItemContent>
        </Item>
    )
}

export default AuditEntryRow
