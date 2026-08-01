import LiveSparkline from '@/features/servers/components/client/LiveSparkline.tsx'
import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import useLiveMetrics from '@/features/servers/hooks/use-live-metrics.ts'
import { IconCpu } from '@tabler/icons-react'

import Skeleton from '@/components/ui/Skeleton.tsx'

const CpuUsageCard = () => {
    const { metrics, data: state, isUnknown } = useLiveMetrics()

    return (
        <StatisticCard
            title={'CPU Usage'}
            icon={IconCpu}
            /* Always present, so the band is part of the card's height from
               the first paint. Rendering it only once a reading landed grew
               every tile in the row by 20px the moment the poll returned --
               the grid stretches them all to the tallest. The trace itself
               draws nothing until it has two samples, so an unfilled band is
               simply empty rather than a flat line at zero. */
            trend={
                <LiveSparkline
                    metrics={metrics}
                    series='cpu'
                    color='var(--chart-cpu)'
                    ceiling={100}
                />
            }
        >
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
