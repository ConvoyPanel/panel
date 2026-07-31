import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import { useServerState } from '@/features/servers/detail/api.ts'
import { cn } from '@/utils'
import { IconPlaystationCircle } from '@tabler/icons-react'

import Skeleton from '@/components/ui/Skeleton.tsx'

const ServerStateCard = () => {
    const { data: state, isUnknown } = useServerState()

    return (
        <StatisticCard title={'Server State'} icon={IconPlaystationCircle}>
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
                <p
                    className={
                        'flex items-center text-lg font-semibold tracking-tight @sm:text-2xl'
                    }
                >
                    <span className='relative mx-1 mr-2 flex h-2 w-2 @sm:mr-4'>
                        {state.powerState === 'running' && (
                            <span className='absolute inline-flex h-full w-full animate-ping rounded-full bg-green-600 opacity-75' />
                        )}
                        <span
                            className={cn(
                                'relative inline-flex h-2 w-2 rounded-full',
                                state.powerState === 'running'
                                    ? 'bg-green-600'
                                    : 'bg-destructive'
                            )}
                        />
                    </span>
                    {state.powerState === 'running' ? 'Running' : 'Stopped'}
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default ServerStateCard
