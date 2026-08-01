import MetricPlot from '@/features/servers/components/client/Graphs/MetricPlot.tsx'
import MetricRail from '@/features/servers/components/client/Graphs/MetricRail.tsx'
import type {
    Metric,
    PanelPoint,
} from '@/features/servers/components/client/Graphs/metrics.ts'
import type { LiveMetrics } from '@/features/servers/hooks/use-live-metrics.ts'
import type { Server } from '@/types/server'
import { Fragment } from 'react'

interface Props {
    metric: Metric
    points: PanelPoint[]
    server: Server | undefined
    /** Live buffers, for the two metrics the guest reports in real time. */
    metrics: LiveMetrics
    /** Whether this metric has a live reading at all. */
    live: boolean
    /** Current figure per series, in `seriesKeys` order. */
    railValues: (number | undefined)[]
    /** True once we know no figure is coming, so the rail stops pulsing. */
    railUnavailable: boolean
    /** When the rail figure was recorded, for metrics with no live feed. */
    sampledAt?: Date
    isLoading: boolean
    /** Only the bottom row draws the shared time axis. */
    isLast: boolean
    xTickFormatter: (value: Date) => string
    stampFormatter: (value: Date) => string
    isFirst: boolean
}

/**
 * One metric across the panel's two columns: its rail, and its plot.
 *
 * The two halves are kept apart on purpose. The rail carries live figures and
 * redraws as they arrive; the plot only ever changes when the history or the
 * range does, and `MetricPlot` is memoised on exactly that so the live feed
 * cannot reach into the chart.
 */
const MetricRow = ({
    metric,
    points,
    server,
    metrics,
    live,
    railValues,
    railUnavailable,
    sampledAt,
    isLoading,
    isFirst,
    isLast,
    xTickFormatter,
    stampFormatter,
}: Props) => (
    <Fragment>
        <MetricRail
            metric={metric}
            server={server}
            metrics={metrics}
            live={live}
            values={railValues}
            unavailable={railUnavailable}
            sampledAt={sampledAt}
            isFirst={isFirst}
            isLast={isLast}
        />

        <MetricPlot
            metric={metric}
            points={points}
            server={server}
            isLoading={isLoading}
            isLast={isLast}
            xTickFormatter={xTickFormatter}
            stampFormatter={stampFormatter}
        />
    </Fragment>
)

export default MetricRow
