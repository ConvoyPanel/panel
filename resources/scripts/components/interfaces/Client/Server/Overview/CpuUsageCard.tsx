import { IconCpu } from '@tabler/icons-react'

import useServerStateSWR from '@/api/servers/use-server-state-swr.ts'

import StatisticCard from '@/components/interfaces/Client/Server/Overview/StatisticCard.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'


const CpuUsageCard = () => {
    const { data: state } = useServerStateSWR()

    return (
        <StatisticCard title={'CPU Usage'} icon={IconCpu}>
            {state ? (
                <p className={'@sm:text-2xl relative text-lg font-bold'}>
                    {Math.floor(state.cpuUsed * 100)}%
                </p>
            ) : (
                <Skeleton className={'@sm:h-8 h-7 w-full'} />
            )}
        </StatisticCard>
    )
}

export default CpuUsageCard
