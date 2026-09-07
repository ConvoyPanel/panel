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

    const usedPercent =
        state && state.memoryTotal > 0
            ? (state.memoryUsed / state.memoryTotal) * 100
            : 0

    return (
        <StatisticCard
            title={'Memory Usage'}
            icon={IconAirConditioningDisabled}
            /* The total used to hang off the figure as a floated "/ 1 GiB",
               which needed its own absolute positioning to stay out of the
               way and still collided with the figure on a narrow tile. Same
               denominator, on the line every other tile keeps it. */
            context={
                state ? (
                    <>
                        of {total.value} {total.unit} &#x2022;{' '}
                        {usedPercent.toFixed(0)}%
                    </>
                ) : undefined
            }
            /* See CpuUsageCard: reserved from the first paint so the tile
               does not grow when the first reading arrives. */
            trend={
                <LiveSparkline
                    metrics={metrics}
                    series='memory'
                    color='var(--chart-memory)'
                    /* The limit the figure is read against, so the trace and
                       the context line beneath it share a scale. */
                    ceiling={state?.memoryTotal}
                    baseline
                />
            }
        >
            {isUnknown ? (
                <UnknownStat />
            ) : state ? (
                <p
                    className={
                        'text-lg font-semibold tracking-tight @xl:text-2xl'
                    }
                >
                    {used.value} {used.unit}
                </p>
            ) : (
                <Skeleton className={'h-7 w-full @sm:h-8'} />
            )}
        </StatisticCard>
    )
}

export default MemoryUsageCard
