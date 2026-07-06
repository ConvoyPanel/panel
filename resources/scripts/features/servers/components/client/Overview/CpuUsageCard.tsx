import { IconCpu } from '@tabler/icons-react'

import { useServerState } from '@/features/servers/detail/api.ts'

import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'

import Skeleton from '@/components/ui/Skeleton.tsx'


const CpuUsageCard = () => {
    const { data: state } = useServerState()

    return (
        <StatisticCard title={'CPU Usage'} icon={IconCpu}>
            {state ? (
                <p className={'@sm:text-2xl relative text-lg font-semibold tracking-tight'}>
                    {Math.floor((state.cpuUsed ?? 0) * 100)}%
                </p>
            ) : (
                <Skeleton className={'@sm:h-8 h-7 w-full'} />
            )}
        </StatisticCard>
    )
}

export default CpuUsageCard
