import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import { useServerState } from '@/features/servers/detail/api.ts'
import { IconCpu } from '@tabler/icons-react'

import Skeleton from '@/components/ui/Skeleton.tsx'

const CpuUsageCard = () => {
    const { data: state, isUnknown } = useServerState()

    return (
        <StatisticCard title={'CPU Usage'} icon={IconCpu}>
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
                <p
                    className={
                        'relative text-lg font-semibold tracking-tight @sm:text-2xl'
                    }
                >
                    {Math.floor((state.cpuUsed ?? 0) * 100)}%
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default CpuUsageCard
