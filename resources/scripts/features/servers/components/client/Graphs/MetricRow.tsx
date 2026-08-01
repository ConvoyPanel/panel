import LiveSparkline from '@/features/servers/components/client/Graphs/LiveSparkline.tsx'
import TweenedValue from '@/features/servers/components/client/Graphs/TweenedValue.tsx'
import {
    type Metric,
    seriesKeys,
    seriesLabels,
} from '@/features/servers/components/client/Graphs/metrics.ts'
import type {
    LiveMetricKey,
    LiveMetrics,
} from '@/features/servers/components/client/Graphs/use-live-metrics.ts'
import type { Server } from '@/types/server'
import { cn } from '@/utils'
import { Fragment, type RefObject, useId } from 'react'
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts'

import { ChartContainer, ChartTooltip } from '@/components/ui/Chart'
import Skeleton from '@/components/ui/Skeleton.tsx'

import LiveIndicator from './LiveIndicator.tsx'

export interface PanelPoint {
    timestamp: Date
    [series: string]: number | Date
}

interface Props {
    metric: Metric
    points: PanelPoint[]
    server: Server | undefined
    /** Live buffers, for the two metrics the guest reports in real time. */
    metrics: RefObject<LiveMetrics>
    /** Whether this metric has a live reading at all. */
    live: boolean
    /** Current live value, or the newest historical sample when there is none. */
    railValue: number | undefined
    isLoading: boolean
    /** Only the bottom row draws the shared time axis. */
    isLast: boolean
    xTickFormatter: (value: Date) => string
    onActiveIndex: (index: number | null) => void
}

/** Y axis width. Identical on every row, which is what aligns the plots. */
const AXIS_WIDTH = 68

const CHART_MARGIN = { top: 6, right: 8, bottom: 0, left: 0 }

const MetricRow = ({
    metric,
    points,
    server,
    metrics,
    live,
    railValue,
    isLoading,
    isLast,
    xTickFormatter,
    onActiveIndex,
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
        const peak = points.reduce((max, point) => {
            return keys.reduce((rowMax, key) => {
                const value = point[key]
                return typeof value === 'number'
                    ? Math.max(rowMax, Math.abs(value))
                    : rowMax
            }, max)
        }, 0)
        ceiling = peak * 1.15 || 1
    }

    const domain: [number, number] = mirrored
        ? [-ceiling, ceiling]
        : [0, ceiling]

    const ticks = mirrored
        ? [-ceiling, 0, ceiling]
        : metric.key === 'cpu'
          ? [0, 25, 50, 75, 100]
          : undefined

    return (
        <Fragment>
            {/* Rail cell — the live column. */}
            <div
                className={cn(
                    'relative flex flex-col justify-center gap-0.5 p-3 pl-4',
                    !isLast && 'border-b',
                    '@3xl:border-r'
                )}
            >
                {/* Ties the readout to its trace by colour without printing
                    the figure itself in the series hue. */}
                <span
                    aria-hidden
                    className='absolute top-3 bottom-3 left-0 w-0.5 rounded-full'
                    style={{ background: metric.color }}
                />
                <div className='flex items-center gap-2'>
                    <span className='text-sm font-medium'>{metric.name}</span>
                    {live && <LiveIndicator />}
                </div>
                {railValue === undefined ? (
                    <Skeleton className='h-6 w-24' />
                ) : (
                    <TweenedValue
                        value={railValue}
                        format={metric.format}
                        className='text-xl font-semibold tracking-tight'
                    />
                )}
                <span className='text-xs text-muted-foreground tabular-nums'>
                    {metric.caption(server)}
                </span>

                {live && (
                    <LiveSparkline
                        metrics={metrics}
                        series={metric.key as LiveMetricKey}
                        color={metric.color}
                        ceiling={metric.ceiling?.(server)}
                        className='mt-2 h-7'
                    />
                )}

                {mirrored && (
                    <div className='mt-1.5 flex items-center gap-3 text-[11px] text-muted-foreground'>
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

            {/* Plot cell. */}
            <div
                className={cn(
                    'px-3 pt-3',
                    isLast ? 'pb-1' : 'border-b pb-2'
                )}
            >
                {isLoading ? (
                    <Skeleton className={cn('w-full', isLast ? 'h-32' : 'h-24')} />
                ) : (
                    <ChartContainer
                        config={{}}
                        className={cn(
                            'aspect-auto w-full',
                            isLast ? 'h-32' : 'h-24'
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
                            onMouseMove={(state: any) =>
                                onActiveIndex(
                                    typeof state?.activeTooltipIndex ===
                                        'number'
                                        ? state.activeTooltipIndex
                                        : null
                                )
                            }
                            onMouseLeave={() => onActiveIndex(null)}
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
                                /* One time axis for the panel, drawn once at
                                   the bottom. The rows above are the same
                                   scale; repeating it four times would be
                                   three rows of noise. */
                                hide={!isLast}
                                height={26}
                                minTickGap={48}
                                tickFormatter={xTickFormatter}
                                tickLine={false}
                                axisLine={false}
                            />

                            {/* Draws and synchronises the cursor line. The
                                readout itself is rendered once for the whole
                                panel, so this contributes no box of its own. */}
                            <ChartTooltip
                                cursor={{
                                    stroke: 'var(--border)',
                                    strokeWidth: 1,
                                }}
                                content={() => null}
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
                                    activeDot={false}
                                    isAnimationActive={false}
                                />
                            ))}
                        </AreaChart>
                    </ChartContainer>
                )}
            </div>
        </Fragment>
    )
}

export default MetricRow
