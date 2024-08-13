import { cn } from '@/utils'
import { IconPlaystationCircle } from '@tabler/icons-react'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'


const ServerStateCard = () => {
    const { data: state } = useServerStateSWR()

    return (
        <StatisticCard title={'Server State'} icon={IconPlaystationCircle}>
            {state ? (
                <p
                    className={
                        '@sm:text-2xl flex items-center text-lg font-bold'
                    }
                >
                    <span className='@sm:mr-4 relative mx-1 mr-2 flex h-2 w-2'>
                        {state.state === 'running' && (
                            <span className='absolute inline-flex h-full w-full animate-ping rounded-full bg-green-600 opacity-75' />
                        )}
                        <span
                            className={cn(
                                'relative inline-flex h-2 w-2 rounded-full',
                                state.state === 'running'
                                    ? 'bg-green-600'
                                    : 'bg-destructive'
                            )}
                        />
                    </span>
                    {state.state === 'running' ? 'Running' : 'Stopped'}
                </p>
            ) : (
                <Skeleton className={'@sm:h-8 h-7 w-full'} />
            )}
        </StatisticCard>
    )
}

export default ServerStateCard
