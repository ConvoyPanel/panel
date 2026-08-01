import LiveSparkline from '@/features/servers/components/client/Graphs/LiveSparkline.tsx'
import TweenedValue from '@/features/servers/components/client/Graphs/TweenedValue.tsx'
import {
    type Metric,
    seriesLabels,
} from '@/features/servers/components/client/Graphs/metrics.ts'
import type {
    LiveMetricKey,
    LiveMetrics,
} from '@/features/servers/components/client/Graphs/use-live-metrics.ts'
import type { Server } from '@/types/server'
import { cn } from '@/utils'
import { type RefObject } from 'react'

import Skeleton from '@/components/ui/Skeleton.tsx'

import LiveIndicator from './LiveIndicator.tsx'

interface Props {
    metric: Metric
    server: Server | undefined
    metrics: RefObject<LiveMetrics>
    /** Whether this metric has a live reading at all. */
    live: boolean
    /** Current live value, or the newest historical sample when there is none. */
    value: number | undefined
    /**
     * Whether we already know no value is coming. A missing value alone means
     * "still loading" and shows a skeleton; this distinguishes it from "asked
     * and there is no answer", which must not pulse forever.
     */
    unavailable: boolean
    isLast: boolean
}

/**
 * One metric's cell in the live column: its name, current figure, and -- for
 * the two metrics the guest reports in real time -- a scrolling trace of the
 * last minute.
 *
 * Split out from the row because it outlives the plot beside it. The rail reads
 * live guest state while the plots read the statistics endpoint, and those fail
 * independently: a node whose RRD is unreachable can still be running a guest
 * that answers perfectly well for CPU and memory.
 */
const MetricRail = ({
    metric,
    server,
    metrics,
    live,
    value,
    unavailable,
    isLast,
}: Props) => {
    const mirrored = metric.shape.kind === 'mirrored'
    const labels = seriesLabels(metric)

    return (
        <div
            className={cn(
                'relative flex flex-col justify-center gap-0.5 p-3 pl-4',
                !isLast && 'border-b',
                '@3xl:border-r'
            )}
        >
            {/* Ties the figure to its trace by colour without printing the
                figure itself in the series hue. */}
            <span
                aria-hidden
                className='absolute top-3 bottom-3 left-0 w-0.5 rounded-full'
                style={{ background: metric.color }}
            />
            <div className='flex items-center gap-2'>
                <span className='text-sm font-medium'>{metric.name}</span>
                {live && !unavailable && <LiveIndicator />}
            </div>

            {value !== undefined ? (
                <TweenedValue
                    value={value}
                    format={metric.format}
                    className='text-xl font-semibold tracking-tight'
                />
            ) : unavailable ? (
                <span
                    className='text-muted-foreground text-xl font-semibold tracking-tight'
                    title='No reading available'
                >
                    &mdash;
                </span>
            ) : (
                <Skeleton className='h-6 w-24' />
            )}

            <span className='text-muted-foreground text-xs tabular-nums'>
                {metric.caption(server)}
            </span>

            {live && value !== undefined && (
                <LiveSparkline
                    metrics={metrics}
                    series={metric.key as LiveMetricKey}
                    color={metric.color}
                    ceiling={metric.ceiling?.(server)}
                    className='mt-2 h-7'
                />
            )}

            {mirrored && (
                <div className='text-muted-foreground mt-1.5 flex items-center gap-3 text-[11px]'>
                    {labels.map((label, index) => (
                        <span
                            key={label}
                            className='inline-flex items-center gap-1.5'
                        >
                            <i
                                aria-hidden
                                className='block size-2 rounded-[2px]'
                                style={{
                                    background: metric.color,
                                    opacity: index === 0 ? 1 : 0.45,
                                }}
                            />
                            {label}
                        </span>
                    ))}
                </div>
            )}
        </div>
    )
}

export default MetricRail
