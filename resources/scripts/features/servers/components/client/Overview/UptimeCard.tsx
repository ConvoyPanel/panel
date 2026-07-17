import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import { useServerState } from '@/features/servers/detail/api.ts'
import { IconStopwatch } from '@tabler/icons-react'
import { addSeconds, formatDistance } from 'date-fns'

import Skeleton from '@/components/ui/Skeleton.tsx'

const UptimeCard = () => {
    const { data: state, isUnknown } = useServerState()

    return (
        <StatisticCard title={'Uptime'} icon={IconStopwatch}>
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
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
