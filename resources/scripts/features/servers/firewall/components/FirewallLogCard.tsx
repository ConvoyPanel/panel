import {
    type FirewallLogEntry,
    useFirewallLog,
} from '@/features/servers/firewall/api.ts'
import { IconArrowDown, IconArrowUp, IconRefresh } from '@tabler/icons-react'
import { useState } from 'react'

import { Badge } from '@/components/ui/Badge.tsx'
import { Button } from '@/components/ui/Button'
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Switch } from '@/components/ui/Switch'

const PAGE_SIZE = 50

const actionVariant = {
    ACCEPT: 'default',
    DROP: 'destructive',
    REJECT: 'secondary',
} as const

const actionLabel = {
    ACCEPT: 'Accept',
    DROP: 'Drop',
    REJECT: 'Reject',
} as const

const formatTime = (value: string | null): string => {
    if (!value) return '—'

    const date = new Date(value)

    return Number.isNaN(date.getTime())
        ? '—'
        : date.toLocaleTimeString(undefined, {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit',
          })
}

/**
 * One log line. The parsed fields are best-effort — Proxmox's format is not a
 * contract — so a line that did not parse still shows, as its raw text.
 */
const LogRow = ({ entry }: { entry: FirewallLogEntry }) => {
    if (!entry.action && !entry.sourceAddress) {
        return (
            <li
                className={
                    'border-b px-4 py-1.5 font-mono text-xs break-all text-muted-foreground last:border-b-0'
                }
            >
                {entry.raw}
            </li>
        )
    }

    return (
        <li
            className={
                'flex items-center gap-3 border-b px-4 py-1.5 text-xs last:border-b-0'
            }
            title={entry.raw}
        >
            <span
                className={
                    'w-20 shrink-0 font-mono text-muted-foreground tabular-nums'
                }
            >
                {formatTime(entry.loggedAt)}
            </span>

            <span className={'w-16 shrink-0'}>
                {entry.action && (
                    <Badge variant={actionVariant[entry.action]}>
                        {actionLabel[entry.action]}
                    </Badge>
                )}
            </span>

            <span className={'w-6 shrink-0 text-muted-foreground'}>
                {entry.direction === 'in' ? (
                    <IconArrowDown className={'size-3'} />
                ) : entry.direction === 'out' ? (
                    <IconArrowUp className={'size-3'} />
                ) : null}
            </span>

            <span className={'min-w-0 flex-1 truncate font-mono'}>
                {entry.sourceAddress ?? '?'}
                <span className={'px-1.5 text-muted-foreground'}>→</span>
                {entry.destinationAddress ?? '?'}
            </span>

            <span className={'shrink-0 font-mono text-muted-foreground'}>
                {entry.protocol}
                {entry.destinationPort != null && `/${entry.destinationPort}`}
            </span>
        </li>
    )
}

interface Props {
    uuid: string
}

const FirewallLogCard = ({ uuid }: Props) => {
    const [start, setStart] = useState(0)
    const [autoRefresh, setAutoRefresh] = useState(false)

    // Only meaningful while looking at the newest page; refreshing under a user
    // who has paged back would pull the ground out from under them.
    const isLive = autoRefresh && start === 0

    const {
        data: entries,
        isLoading,
        isFetching,
        refetch,
    } = useFirewallLog(uuid, start, PAGE_SIZE, isLive ? 10_000 : false)

    return (
        <Card>
            <CardHeader>
                <CardTitle>Recent activity</CardTitle>
                <CardDescription>
                    Packets the firewall logged. Useful for working out why
                    something cannot connect.
                </CardDescription>
                <CardAction className={'flex items-center gap-3'}>
                    <label
                        className={
                            'flex items-center gap-2 text-xs text-muted-foreground'
                        }
                    >
                        Auto-refresh
                        <Switch
                            checked={autoRefresh}
                            onCheckedChange={setAutoRefresh}
                            disabled={start !== 0}
                        />
                    </label>
                    <Button
                        variant={'outline'}
                        size={'sm'}
                        onClick={() => refetch()}
                        loading={isFetching}
                    >
                        <IconRefresh className={'size-4'} />
                        Refresh
                    </Button>
                </CardAction>
            </CardHeader>

            <CardContent className={'flex-1 px-0'}>
                {isLoading ? (
                    <Skeleton className={'mx-4 h-40'} />
                ) : !entries?.length ? (
                    <SimpleEmptyState
                        icon={IconRefresh}
                        title={'Nothing logged'}
                        description={
                            'Turn on logging above to record the packets this firewall drops.'
                        }
                    />
                ) : (
                    <ul>
                        {entries.map(entry => (
                            <LogRow key={entry.lineNumber} entry={entry} />
                        ))}
                    </ul>
                )}
            </CardContent>

            <CardFooter className={'gap-3 text-xs text-muted-foreground'}>
                <span>
                    {!entries?.length
                        ? 'No entries on this page'
                        : isLive
                          ? `Showing the latest ${entries.length} entries`
                          : `Showing entries ${start + 1}–${start + entries.length}`}
                </span>
                <span className={'flex-1'} />
                <Button
                    variant={'outline'}
                    size={'sm'}
                    disabled={start === 0}
                    onClick={() => setStart(Math.max(start - PAGE_SIZE, 0))}
                >
                    Newer
                </Button>
                <Button
                    variant={'outline'}
                    size={'sm'}
                    disabled={(entries?.length ?? 0) < PAGE_SIZE}
                    onClick={() => setStart(start + PAGE_SIZE)}
                >
                    Older
                </Button>
            </CardFooter>
        </Card>
    )
}

export default FirewallLogCard
