import { IconStopwatch } from '@tabler/icons-react'
import { addSeconds, formatDistance } from 'date-fns'

import { useServerState } from '@/features/servers/detail/api.ts'

import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'


const UptimeCard = () => {
    const { data: state } = useServerState()

    return (
        <StatisticCard title={'Uptime'} icon={IconStopwatch}>
            {state ? (
                <p
                    className={
                        'relative truncate text-lg font-semibold tracking-tight @sm:text-2xl'
                    }
                >
                    {formatDistance(
                        new Date(),
                        addSeconds(new Date(), state.uptime)
                    )}
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default UptimeCard
