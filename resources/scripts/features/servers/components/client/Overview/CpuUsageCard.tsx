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
            /* Only once a reading has landed: the trace draws nothing until it
               has two samples, and an empty band under the figure reads as a
               rule rather than a chart waiting to fill. */
            trend={
                state && !isUnknown ? (
                    <LiveSparkline
                        metrics={metrics}
                        series='cpu'
                        color='var(--chart-cpu)'
                        ceiling={100}
                    />
                ) : undefined
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
