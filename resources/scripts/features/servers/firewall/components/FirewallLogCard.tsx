import {
    type FirewallLogEntry,
    type FirewallOptions,
    isLogging,
    useFirewallLog,
    useFirewallOptions,
    withLogging,
} from '@/features/servers/firewall/api.ts'
import useUpdateFirewallOptions from '@/features/servers/firewall/use-update-options.ts'
import {
    IconArrowDown,
    IconArrowUp,
    IconLogs,
    IconRefresh,
} from '@tabler/icons-react'
import { useState } from 'react'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Switch } from '@/components/ui/Switch'

const PAGE_SIZE = 50

const actionStyles = {
    ACCEPT: 'text-success',
    DROP: 'text-destructive',
    REJECT: 'text-destructive/80',
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
 * One log line. The parsed fields are best-effort -- Proxmox's format is not a
 * contract -- so a line that did not parse still shows, as its raw text.
 */
const LogRow = ({ entry }: { entry: FirewallLogEntry }) => {
    if (!entry.action && !entry.sourceAddress) {
        return (
            <li
                className={
                    'text-muted-foreground border-b px-4 py-1.5 font-mono text-xs break-all last:border-b-0'
                }
            >
                {entry.raw}
            </li>
        )
    }

    return (
        <li
            className={
                'flex items-center gap-3 border-b px-4 py-1.5 font-mono text-xs last:border-b-0'
            }
            title={entry.raw}
        >
            <span
                className={'text-muted-foreground w-20 shrink-0 tabular-nums'}
            >
                {formatTime(entry.loggedAt)}
            </span>

            <span
                className={`w-14 shrink-0 font-semibold tracking-wide ${entry.action ? actionStyles[entry.action] : ''}`}
            >
                {entry.action}
            </span>

            <span className={'text-muted-foreground w-4 shrink-0'}>
                {entry.direction === 'in' ? (
                    <IconArrowDown className={'size-3'} />
                ) : entry.direction === 'out' ? (
                    <IconArrowUp className={'size-3'} />
                ) : null}
            </span>

            <span className={'min-w-0 flex-1 truncate'}>
                {entry.sourceAddress ?? '?'}
                <span className={'text-muted-foreground px-1.5'}>→</span>
                {entry.destinationAddress ?? '?'}
            </span>

            <span className={'text-muted-foreground shrink-0'}>
                {entry.protocol}
                {entry.destinationPort != null && `/${entry.destinationPort}`}
            </span>
        </li>
    )
}

/** Which directions are recording, for the bar's status line. */
const describeLoggedDirections = (options: FirewallOptions): string => {
    const inbound = isLogging(options.inboundLogLevel)
    const outbound = isLogging(options.outboundLogLevel)

    if (inbound && outbound) return 'inbound and outbound'

    return inbound ? 'inbound' : 'outbound'
}

interface Props {
    uuid: string
}

/**
 * What the firewall actually did, most recent first.
 *
 * With nothing to show and nothing configured to log, this collapses to its own
 * header and the switch that would fill it. The card it replaced spent a third
 * of the page telling the reader to go and find a control in a different card
 * at the top of the screen.
 */
const FirewallLogCard = ({ uuid }: Props) => {
    const [start, setStart] = useState(0)
    const [autoRefresh, setAutoRefresh] = useState(false)

    const { data: options } = useFirewallOptions(uuid)
    const { mutate: saveOptions, isPending: isSavingOptions } =
        useUpdateFirewallOptions(uuid)

    // Only meaningful while looking at the newest page; refreshing under a user
    // who has paged back would pull the ground out from under them.
    const isLive = autoRefresh && start === 0

    const {
        data: entries,
        isLoading,
        isFetching,
        refetch,
    } = useFirewallLog(uuid, start, PAGE_SIZE, isLive ? 10_000 : false)

    /*
     * The default policy is not the only thing that writes to this log -- an
     * individual rule can set its own log level too. So an empty list is only
     * evidence that nothing is configured when the policies also say so, and
     * entries are shown whenever they exist regardless of these switches.
     */
    const isLoggingUnmatched =
        options !== undefined &&
        (isLogging(options.inboundLogLevel) ||
            isLogging(options.outboundLogLevel))

    const hasEntries = Boolean(entries?.length)

    // `options !== undefined` is part of the condition, not a null guard: the
    // two queries settle independently, and without it the card renders its
    // dormant one-liner for a frame while the options are still in flight.
    const isDormant =
        !isLoading &&
        options !== undefined &&
        !hasEntries &&
        !isLoggingUnmatched

    const status = isDormant
        ? 'Nothing is being logged'
        : options && isLoggingUnmatched
          ? `Logging unmatched ${describeLoggedDirections(options)} traffic`
          : 'Logged by individual rules'

    return (
        <Card>
            {/* A plain bar rather than CardHeader: this card sits under the
                ledger, whose chains use the same bar, and a title-plus-sentence
                header here would read as a third unrelated surface. */}
            <div
                className={
                    'flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3'
                }
            >
                <h3 className={'font-medium'}>Recent activity</h3>
                <span className={'text-muted-foreground text-xs'}>
                    {status}
                </span>
                <span className={'flex-1'} />

                {isDormant ? (
                    <Button
                        variant={'outline'}
                        size={'sm'}
                        loading={isSavingOptions}
                        onClick={() =>
                            options &&
                            saveOptions(withLogging(options, 'in', true))
                        }
                    >
                        Log dropped inbound traffic
                    </Button>
                ) : (
                    <>
                        <label
                            className={
                                'text-muted-foreground flex items-center gap-2 text-xs'
                            }
                        >
                            Live
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
                            icon={<IconRefresh className={'size-4'} />}
                        >
                            Refresh
                        </Button>
                    </>
                )}
            </div>

            {!isDormant && (
                <>
                    <div className={'border-t'}>
                        {isLoading ? (
                            <Skeleton className={'m-4 h-24'} />
                        ) : hasEntries ? (
                            <ul>
                                {(entries ?? []).map(entry => (
                                    <LogRow
                                        key={entry.lineNumber}
                                        entry={entry}
                                    />
                                ))}
                            </ul>
                        ) : (
                            <SimpleEmptyState
                                icon={IconLogs}
                                title={'Nothing logged yet'}
                                description={
                                    'Packets will appear here as the firewall logs them.'
                                }
                            />
                        )}
                    </div>

                    {(hasEntries || start > 0) && (
                        <div
                            className={
                                'text-muted-foreground flex items-center gap-3 border-t px-4 py-3 text-xs'
                            }
                        >
                            <span>
                                {isLive
                                    ? `Latest ${entries?.length ?? 0} entries`
                                    : `Entries ${start + 1}–${start + (entries?.length ?? 0)}`}
                            </span>
                            <span className={'flex-1'} />
                            <Button
                                variant={'outline'}
                                size={'sm'}
                                disabled={start === 0}
                                onClick={() =>
                                    setStart(Math.max(start - PAGE_SIZE, 0))
                                }
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
                        </div>
                    )}
                </>
            )}
        </Card>
    )
}

export default FirewallLogCard
