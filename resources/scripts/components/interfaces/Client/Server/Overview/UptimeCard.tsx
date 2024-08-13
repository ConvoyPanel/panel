import { IconStopwatch } from '@tabler/icons-react'
import { addSeconds, formatDistance } from 'date-fns'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'


const UptimeCard = () => {
    const { data: state } = useServerStateSWR()

    return (
        <StatisticCard title={'Uptime'} icon={IconStopwatch}>
            {state ? (
                <p className={'@sm:text-2xl relative text-lg font-bold'}>
                    {formatDistance(
                        new Date(),
                        addSeconds(new Date(), state.uptime)
                    )}
                </p>
            ) : (
                <Skeleton className={'@sm:h-8 h-7 w-full'} />
            )}
        </StatisticCard>
    )
}

export default UptimeCard
