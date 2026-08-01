import LiveSparkline from '@/features/servers/components/client/LiveSparkline.tsx'
import StatisticCard from '@/features/servers/components/client/Overview/StatisticCard.tsx'
import UnknownStat from '@/features/servers/components/client/Overview/UnknownStat.tsx'
import useLiveMetrics from '@/features/servers/hooks/use-live-metrics.ts'
import { IconAirConditioningDisabled } from '@tabler/icons-react'
import byteSize from 'byte-size'

import Skeleton from '@/components/ui/Skeleton.tsx'

const MemoryUsageCard = () => {
    const { metrics, data: state, isUnknown } = useLiveMetrics()

    const used = byteSize(state?.memoryUsed ?? 0, {
        units: 'iec',
        precision: 2,
    })
    const total = byteSize(state?.memoryTotal ?? 0, {
        units: 'iec',
        precision: 2,
    })

    return (
        <StatisticCard
            title={'Memory Usage'}
            icon={IconAirConditioningDisabled}
            trend={
                state && !isUnknown ? (
                    <LiveSparkline
                        metrics={metrics}
                        series='memory'
                        color='var(--chart-memory)'
                        /* The limit the figure is read against, so the trace
                           and the "/ 1 GiB" beside it share a scale. */
                        ceiling={state.memoryTotal}
                    />
                ) : undefined
            }
        >
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
                <p className={'relative'}>
                    <span
                        className={
                            'inline-block text-lg font-semibold tracking-tight @xl:text-2xl'
                        }
                    >
                        {used.value} {used.unit}
                    </span>
                    <span
                        className={
                            'text-muted-foreground absolute right-0 -bottom-2.5 ml-1.5 text-xs @sm:right-auto @sm:bottom-auto @sm:mt-1'
                        }
                    >
                        / {total.value} {total.unit}
                    </span>
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default MemoryUsageCard
