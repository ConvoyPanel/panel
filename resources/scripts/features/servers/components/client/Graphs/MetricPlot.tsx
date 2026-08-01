import {
    type Metric,
    type PanelPoint,
    seriesKeys,
    seriesLabels,
} from '@/features/servers/components/client/Graphs/metrics.ts'
import type { Server } from '@/types/server'
import { cn } from '@/utils'
import { memo, useId } from 'react'
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts'

import { ChartContainer, ChartTooltip } from '@/components/ui/Chart'
import Skeleton from '@/components/ui/Skeleton.tsx'

import PanelReadout from './PanelReadout.tsx'

interface Props {
    metric: Metric
    points: PanelPoint[]
    server: Server | undefined
    isLoading: boolean
    /** Only the bottom row draws the shared time axis. */
    isLast: boolean
    xTickFormatter: (value: Date) => string
    stampFormatter: (value: Date) => string
}

/**
 * Y axis width. Identical on every row, which is what aligns the plots -- and
 * wide enough for the longest tick a byte-rate axis produces ("52 MiB/s"), or
 * Recharts wraps the label onto a second line.
 */
const AXIS_WIDTH = 80

/*
 * No right or bottom inset: the plot runs to its cell's edges, so the area
 * fill meets the card border instead of floating in padding. The bottom-most
 * y tick is dropped (see `ticks` below) so nothing is cropped by that, and the
 * last row reserves its own space for the time axis via `XAxis height`.
 */
const CHART_MARGIN = { top: 0, right: 0, bottom: 0, left: 0 }

/**
 * How far the axis runs past the highest tick.
 *
 * The headroom above the top gridline has to come from somewhere, and a chart
 * margin is the wrong place: Recharts ignores pointer movement in its margins,
 * so a 12px margin is a 12px strip along the top of the row that does not
 * respond to hovering at all. Stretching the domain instead puts the same gap
 * inside the plot, where the pointer still counts.
 */
const HEADROOM = 1.14

/**
 * The same idea for a mirrored row, but larger.
 *
 * A zero-based axis spends all its headroom above the top tick. A mirrored one
 * splits the same fraction between two ends, so it clears each by half as much
 * -- which put the outer labels hard against the top and bottom of the row.
 */
const MIRRORED_HEADROOM = 1.32

/**
 * One row's plot: the chart cell, and on the last row the shared time axis.
 *
 * Deliberately split from `MetricRow` and memoised on props that only change
 * when the data or the range does. The rail beside it redraws on every live
 * reading -- twenty times a second -- and re-rendering the chart at that rate
 * broke the panel's cursor: Recharts clears a synchronised chart by
 * broadcasting a one-shot event when the pointer leaves, and a re-render
 * landing in that window loses it, leaving cursor lines and dots stuck on rows
 * nobody is hovering until the next pass over the panel. Keeping every render
 * that is not about this row's *data* out of the chart subtree is what makes
 * the leave reliable; it also spares four charts a redraw per live sample.
 */
const MetricPlot = ({
    metric,
    points,
    server,
    isLoading,
    isLast,
    xTickFormatter,
    stampFormatter,
}: Props) => {
    const gradientId = useId().replace(/:/g, '')
    const keys = seriesKeys(metric)
    const labels = seriesLabels(metric)
    const mirrored = metric.shape.kind === 'mirrored'

    /* A mirrored row is scaled by the larger of the two directions so the two
       halves share one scale -- read at 40 MiB/s and write at 4 MiB/s must not
       look alike. */
    let ceiling = metric.ceiling?.(server)
    if (!ceiling) {
        ceiling =
            points.reduce((max, point) => {
                return keys.reduce((rowMax, key) => {
                    const value = point[key]
                    return typeof value === 'number'
                        ? Math.max(rowMax, Math.abs(value))
                        : rowMax
                }, max)
            }, 0) || 1
    }

    /* `ceiling` is where the outermost tick goes; the axis runs past it. */
    const limit = ceiling * (mirrored ? MIRRORED_HEADROOM : HEADROOM)

    const domain: [number, number] = mirrored ? [-limit, limit] : [0, limit]

    /*
     * A mirrored row labels both directions: the domain runs past the top
     * tick and below the bottom one by the same headroom, so neither label
     * touches an edge, and the reader gets the scale of the half below the
     * axis instead of having to infer it.
     *
     * A zero-based row still has no tick on its baseline -- there is no
     * headroom under zero, so a label there would be sliced by the cell's
     * bottom edge. Nothing is lost: the floor of a zero-based axis is obvious.
     */
    const ticks = mirrored
        ? [-1, -0.5, 0, 0.5, 1].map(fraction => fraction * ceiling)
        : (metric.tickFractions ?? [0.25, 0.5, 0.75, 1]).map(
              fraction => fraction * ceiling
          )

    /* Six evenly spaced stamps across the window. The samples are evenly
       spaced in time, so an even split of the width lands on real instants. */
    const timeLabels =
        isLast && points.length > 1
            ? Array.from({ length: 6 }, (_, i) =>
                  xTickFormatter(
                      points[Math.round((i / 5) * (points.length - 1))]
                          .timestamp
                  )
              )
            : []

    return (
        <div
            className={cn(
                /* A column so the chart can claim the row's full height:
                   rows are sized by whichever side is taller, and a
                   fixed-height chart left slack under itself that stopped
                   it reaching the bottom edge. */
                /* No padding at all: any padding here is cell that the
                   chart does not cover, and Recharts only tracks the
                   pointer inside its own box -- so a padded strip along
                   the top of each row was simply dead to hovering. The
                   breathing room comes from the chart's own top margin
                   instead, which is inside the hoverable area. */
                'group flex flex-col',
                !isLast && 'border-b'
            )}
        >
            {isLoading ? (
                /* Shaped like the area chart it stands in for -- a band
                   along the baseline -- rather than a full-height slab of
                   solid colour, which reads as a broken panel. */
                <div
                    className={cn(
                        'flex flex-1 items-end',
                        isLast ? 'min-h-44' : 'min-h-36'
                    )}
                >
                    <Skeleton className='h-1/2 w-full rounded-none rounded-tr-sm' />
                </div>
            ) : (
                <ChartContainer
                    config={{}}
                    className={cn(
                        'aspect-auto w-full flex-1',
                        isLast ? 'min-h-44' : 'min-h-36'
                    )}
                >
                    <AreaChart
                        accessibilityLayer
                        data={points}
                        margin={CHART_MARGIN}
                        /* Every row shares this, so Recharts keeps the
                           active index -- and therefore the cursor line --
                           in step across all four plots. */
                        syncId='server-resource-panel'
                    >
                        <defs>
                            <linearGradient
                                id={`${gradientId}-up`}
                                x1='0'
                                y1='0'
                                x2='0'
                                y2='1'
                            >
                                <stop
                                    offset='0%'
                                    stopColor={metric.color}
                                    stopOpacity={0.3}
                                />
                                <stop
                                    offset='100%'
                                    stopColor={metric.color}
                                    stopOpacity={0.02}
                                />
                            </linearGradient>
                            <linearGradient
                                id={`${gradientId}-down`}
                                x1='0'
                                y1='1'
                                x2='0'
                                y2='0'
                            >
                                <stop
                                    offset='0%'
                                    stopColor={metric.color}
                                    stopOpacity={0.3}
                                />
                                <stop
                                    offset='100%'
                                    stopColor={metric.color}
                                    stopOpacity={0.02}
                                />
                            </linearGradient>
                        </defs>

                        <CartesianGrid vertical={false} />

                        <YAxis
                            width={AXIS_WIDTH}
                            domain={domain}
                            ticks={ticks}
                            tickFormatter={metric.formatShort}
                            axisLine={false}
                            tickLine={false}
                            interval='preserveStartEnd'
                        />

                        <XAxis
                            dataKey='timestamp'
                            /* Hidden on every row, including the last.
                               The scale still comes from here -- the
                               cursor and tooltip need it -- but the
                               labels are drawn as HTML below the card so
                               their spacing is ours to control; Recharts
                               pins the final tick to the plot edge, which
                               with a full-bleed plot means flush against
                               the card border. */
                            hide
                        />

                        {/* Every row draws and synchronises a cursor line;
                            only the hovered one opens a box, and which one
                            that is comes from `group-hover` inside the
                            readout rather than from React state -- a row
                            that re-renders mid-interaction is exactly what
                            unsticks Recharts' cursor synchronisation. */}
                        <ChartTooltip
                            cursor={{
                                stroke: 'var(--border)',
                                strokeWidth: 1,
                            }}
                            /* A row is 96px tall and the readout lists six
                               figures, so it has to be allowed out of the
                               plot vertically or it would be cropped. */
                            allowEscapeViewBox={{ x: false, y: true }}
                            wrapperStyle={{ zIndex: 20 }}
                            content={
                                <PanelReadout formatStamp={stampFormatter} />
                            }
                        />

                        {keys.map((key, index) => (
                            <Area
                                key={key}
                                dataKey={key}
                                name={labels[index]}
                                type='monotone'
                                stroke={metric.color}
                                strokeWidth={2}
                                strokeOpacity={index === 0 ? 1 : 0.75}
                                fill={`url(#${gradientId}-${index === 0 ? 'up' : 'down'})`}
                                dot={false}
                                /* Marks where the cursor line crosses this
                                   series, so the figure in the readout is
                                   tied to a point on the trace rather than
                                   left to be eyeballed. Ringed in the card
                                   colour so it stays legible over the fill. */
                                activeDot={{
                                    r: 3.5,
                                    fill: metric.color,
                                    fillOpacity: index === 0 ? 1 : 0.75,
                                    stroke: 'var(--card)',
                                    strokeWidth: 2,
                                }}
                                isAnimationActive={false}
                            />
                        ))}
                    </AreaChart>
                </ChartContainer>
            )}

            {isLast && (
                <div
                    /* Always rendered, at a fixed height, even with no
                       labels to put in it: appearing only once the data
                       landed made the whole card jump taller the moment
                       loading finished.

                       Left inset matches the y axis so the first label
                       sits over the start of the plot; the last is
                       right-aligned with a little room off the border. */
                    className='text-muted-foreground flex h-5 items-center justify-between pr-2 text-xs tabular-nums'
                    style={{ paddingLeft: AXIS_WIDTH }}
                    aria-hidden
                >
                    {timeLabels.map(label => (
                        <span key={label}>{label}</span>
                    ))}
                </div>
            )}
        </div>
    )
}

export default memo(MetricPlot)
