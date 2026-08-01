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
    /**
     * Current figure per series, in `seriesKeys` order -- live where the guest
     * reports it, otherwise the newest historical sample.
     */
    values: (number | undefined)[]
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
    values,
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
                /* Children stretch rather than shrink to their content: the
                   figures right-align into a column of their own, which is
                   what puts air between a label and its value instead of
                   leaving them jammed together at whatever width the widest
                   row happened to be. */
                'relative flex flex-col gap-0.5 p-3 pl-4',
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
            {/* Baseline, not centre: the age is two sizes down from the name,
                and centring the two left it floating off the name's baseline. */}
            <div className='flex items-baseline gap-2'>
                <span className='text-sm font-medium'>{metric.name}</span>
                {live && !unavailable && (
                    <LiveIndicator className='self-center' />
                )}
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

            {mirrored ? (
                /*
                 * Both directions, each against its own name.
                 *
                 * A single figure over the caption "read / write" does not say
                 * which of the two it is -- and it was the first, silently.
                 * Naming them also makes the legend redundant, so the swatches
                 * move here and carry the mapping to the halves of the plot.
                 */
                <div className='mt-0.5 flex flex-col gap-0.5'>
                    {labels.map((label, index) => (
                        <div
                            key={label}
                            /* Centred, not baseline-aligned. A placeholder is
                               an empty block and establishes no text baseline,
                               so while one is in the row the row's baseline
                               comes from the label alone -- and the label
                               jumps a few pixels the moment a figure arrives
                               to share it. The row height never changed; the
                               contents slid inside it. */
                            className='flex w-full items-center gap-1.5'
                        >
                            <i
                                aria-hidden
                                className='size-2 shrink-0 rounded-[2px]'
                                style={{
                                    background: metric.color,
                                    opacity: index === 0 ? 1 : 0.45,
                                }}
                            />
                            <span className='text-muted-foreground pr-3 text-xs'>
                                {label}
                            </span>
                            {values[index] !== undefined ? (
                                <TweenedValue
                                    value={values[index]}
                                    format={metric.format}
                                    className='ml-auto text-sm font-semibold tracking-tight'
                                />
                            ) : unavailable ? (
                                <span className='text-muted-foreground ml-auto text-sm font-semibold'>
                                    &mdash;
                                </span>
                            ) : (
                                /* `h-5` is the text-sm line box this stands
                                   in for, so the row keeps its height. */
                                <Skeleton className='ml-auto h-5 w-16' />
                            )}
                        </div>
                    ))}
                </div>
            ) : (
                <>
                    {values[0] !== undefined ? (
                        <TweenedValue
                            value={values[0]}
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
                        /* `h-7` is the line box of the figure it stands in
                           for; a shorter placeholder made every row resize on
                           load. */
                        <Skeleton className='h-7 w-24' />
                    )}

                    <span className='text-muted-foreground text-xs tabular-nums'>
                        {metric.caption(server)}
                    </span>
                </>
            )}

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
        </div>
    )
}

export default MetricRail
