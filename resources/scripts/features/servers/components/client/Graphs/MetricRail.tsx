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
import { formatDistanceToNowStrict } from 'date-fns'
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
    /**
     * When the shown figure was recorded, for metrics that have no live feed.
     *
     * Disk and network exist only in the statistics endpoint, so their rail
     * figure is the newest sample of the selected range -- which is a minute
     * old on the hourly view and can be days old on the yearly one. Without a
     * stamp it sits next to two genuinely live figures in identical styling
     * and reads as equally current.
     */
    sampledAt?: Date
    isFirst: boolean
    isLast: boolean
}

/** "3m", "2h", "5d" — short enough to sit inline beside the metric name. */
const compactAge = (date: Date) =>
    formatDistanceToNowStrict(date)
        .replace(/ seconds?/, 's')
        .replace(/ minutes?/, 'm')
        .replace(/ hours?/, 'h')
        .replace(/ days?/, 'd')
        .replace(/ months?/, 'mo')
        .replace(/ years?/, 'y')

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
    sampledAt,
    isFirst,
    isLast,
}: Props) => {
    const mirrored = metric.shape.kind === 'mirrored'
    const labels = seriesLabels(metric)

    return (
        <div
            className={cn(
                /* Top-aligned, not centred: the rows are different heights
                   (a sparkline here, a legend there), and centring each one
                   independently left the names, figures and captions sitting
                   at four different heights across the rail. */
                'relative flex flex-col items-start gap-0.5 p-3 pl-4',
                /* The outer cells round off and clip, so the full-height
                   accent stripe follows the card's corner instead of poking
                   out past it. Safe to clip here in a way the card itself is
                   not: the readout opens from the plot cell, not this one. */
                isFirst && 'overflow-hidden rounded-tl-xl',
                isLast && 'overflow-hidden rounded-bl-xl',
                !isLast && 'border-b',
                '@3xl:border-r'
            )}
        >
            {/* Ties the figure to its trace by colour without printing the
                figure itself in the series hue. */}
            <span
                aria-hidden
                /* Full height, so the four stripes butt together into one
                   continuous edge down the card rather than four floating
                   ticks. The cell clips them to the card's corner radius --
                   see the rounding on the container. */
                className='absolute inset-y-0 left-0 w-0.5'
                style={{ background: metric.color }}
            />
            <div className='flex items-center gap-2'>
                <span className='text-sm font-medium'>{metric.name}</span>
                {live && !unavailable && <LiveIndicator />}
                {/* The same slot the live dot occupies, carrying the opposite
                    message: this figure is a reading from the past, not now. */}
                {!live && sampledAt && (
                    <span
                        className='text-muted-foreground ml-auto text-[11px] tabular-nums'
                        title={`Last recorded ${sampledAt.toLocaleString()}`}
                    >
                        {compactAge(sampledAt)} ago
                    </span>
                )}
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
                /* `h-7` is the line box of the figure it stands in for; a
                   shorter placeholder made every row resize on load. */
                <Skeleton className='h-7 w-24' />
            )}

            <span className='text-muted-foreground text-xs tabular-nums'>
                {metric.caption(server)}
            </span>

            {live && (
                <LiveSparkline
                    metrics={metrics}
                    series={metric.key as LiveMetricKey}
                    color={metric.color}
                    ceiling={metric.ceiling?.(server)}
                    /* Bleeds to the cell's edges, cancelling its padding, the
                       way the admin overview's MetricTile trend does. The
                       accent stripe is absolutely positioned so it still
                       paints over the left edge. */
                    /* `w-auto` overrides the component's own `w-full`: an
                       explicit width pins the box, so the negative margins
                       would shift it left without letting it reach the right
                       edge. Stretching in the flex column does the rest. */
                    /* `mt-auto` keeps it pinned to the bottom edge now that
                       the cell packs its content to the top. */
                    className='mt-auto -mr-3 -mb-3 -ml-4 h-8 w-auto self-stretch'
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
