import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import { useServerState } from '@/features/servers/detail/api.ts'
import {
    IconPlayerPlayFilled,
    IconPlayerStopFilled,
    IconPlaystationCircle,
} from '@tabler/icons-react'
import { format, formatDistanceToNowStrict, subSeconds } from 'date-fns'

import Skeleton from '@/components/ui/Skeleton.tsx'

const ServerStateCard = () => {
    const { data: state, isUnknown } = useServerState()

    const isRunning = state?.powerState === 'running'
    /* The state endpoint reports how long the guest has been up, not when it
       booted, so the moment is derived. Printed to the minute: the reading
       refetches many times a second and a seconds-precision timestamp would
       rewrite itself on every one of them. */
    const bootedAt = state ? subSeconds(new Date(), state.uptime) : null

    return (
        <StatisticCard
            title={'Server State'}
            icon={IconPlaystationCircle}
            /* A stopped guest reports no uptime, and nothing says when it
               stopped -- so there is nothing truthful to put here. */
            context={
                isRunning && bootedAt ? (
                    <>
                        up {formatDistanceToNowStrict(bootedAt)} &#x2022;{' '}
                        {format(bootedAt, 'd MMM')}
                        {/* The clock time only where the five-up row has the
                            width for it. At @5xl the tile is ~180px and the
                            line would be cut mid-word; the date is the half
                            worth keeping. */}
                        <span className={'hidden @6xl:inline'}>
                            , {format(bootedAt, 'HH:mm')}
                        </span>
                    </>
                ) : undefined
            }
        >
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
                <p
                    className={
                        'flex items-center gap-2 text-lg font-semibold tracking-tight @sm:text-2xl'
                    }
                >
                    {/*
                     * The shapes on the power controls above, not a status
                     * dot. A dot only carries the state in its colour, and the
                     * ring that used to pulse around it animated forever to
                     * report a value that changes twice a month.
                     */}
                    {isRunning ? (
                        <IconPlayerPlayFilled
                            className={
                                'text-success size-3.5 shrink-0 @sm:size-4'
                            }
                        />
                    ) : (
                        <IconPlayerStopFilled
                            className={
                                'text-destructive size-3.5 shrink-0 @sm:size-4'
                            }
                        />
                    )}
                    {isRunning ? 'Running' : 'Stopped'}
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default ServerStateCard
