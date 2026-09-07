import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import { useServerState } from '@/features/servers/detail/api.ts'
import { cn } from '@/utils'
import { IconPlaystationCircle } from '@tabler/icons-react'
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
                        up {formatDistanceToNowStrict(bootedAt)} &#x2022; since{' '}
                        {format(bootedAt, 'd MMM, HH:mm')}
                    </>
                ) : undefined
            }
        >
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
                <p
                    className={
                        'flex items-center text-lg font-semibold tracking-tight @sm:text-2xl'
                    }
                >
                    <span className='relative mx-1 mr-2 flex h-2 w-2 @sm:mr-4'>
                        {isRunning && (
                            <span className='absolute inline-flex h-full w-full animate-ping rounded-full bg-green-600 opacity-75' />
                        )}
                        <span
                            className={cn(
                                'relative inline-flex h-2 w-2 rounded-full',
                                isRunning ? 'bg-green-600' : 'bg-destructive'
                            )}
                        />
                    </span>
                    {isRunning ? 'Running' : 'Stopped'}
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default ServerStateCard
