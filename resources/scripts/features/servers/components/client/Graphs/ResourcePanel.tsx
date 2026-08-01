import MetricRow, {
    type PanelPoint,
} from '@/features/servers/components/client/Graphs/MetricRow.tsx'
import PanelReadout from '@/features/servers/components/client/Graphs/PanelReadout.tsx'
import { METRICS, seriesKeys } from '@/features/servers/components/client/Graphs/metrics.ts'
import useLiveMetrics from '@/features/servers/components/client/Graphs/use-live-metrics.ts'
import {
    type TimeRange,
    useServer,
    useServerStatistics,
} from '@/features/servers/detail/api.ts'
import { useCallback, useMemo, useRef, useState } from 'react'

import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'

interface Props {
    from: TimeRange
    /** Formats a timestamp for the x axis at the current range. */
    xTickFormatter: (value: Date) => string
}

const clamp = (value: number, min: number, max: number) =>
    value < min ? min : value > max ? max : value

/**
 * The whole of the graphs page: four metrics stacked on one time axis, with a
 * live rail down the left.
 *
 * The four separate cards this replaces could not answer the question people
 * actually bring to this page -- "what else was happening when CPU spiked?" --
 * because comparing across them meant eyeballing two charts whose axes were
 * independently ranged and, until now, independently *time*-ranged as well.
 * One panel, one x axis, one cursor.
 */
const ResourcePanel = ({ from, xTickFormatter }: Props) => {
    const { data: server } = useServer()
    const { data, isPending, isError } = useServerStatistics({ from })
    const { metrics, data: state, isUnknown } = useLiveMetrics()

    const [activeIndex, setActiveIndex] = useState<number | null>(null)

    const panelRef = useRef<HTMLDivElement>(null)
    const readoutRef = useRef<HTMLDivElement>(null)

    /* Every row plots the same array so their indices line up, which is what
       lets one cursor position mean the same instant on all four. Mirrored
       metrics store their second direction negated -- that, not a second hue,
       is what separates read from write. */
    const points = useMemo<PanelPoint[]>(() => {
        if (!data) return []

        return data.map(timepoint => {
            const point: PanelPoint = { timestamp: timepoint.timestamp }

            for (const metric of METRICS) {
                const values = metric.fromTimepoint(timepoint)
                const keys = seriesKeys(metric)

                keys.forEach((key, index) => {
                    point[key] =
                        metric.shape.kind === 'mirrored' && index === 1
                            ? -values[index]
                            : values[index]
                })
            }

            return point
        })
    }, [data])

    /**
     * Follow the pointer by writing to the node directly. Putting the cursor
     * position in state would re-render four Recharts trees on every mouse
     * move, which is enough to make the panel feel heavy.
     */
    const handleMouseMove = useCallback((event: React.MouseEvent) => {
        const panel = panelRef.current
        const readout = readoutRef.current
        if (!panel || !readout) return

        const bounds = panel.getBoundingClientRect()
        const width = readout.offsetWidth
        const height = readout.offsetHeight

        const x = clamp(
            event.clientX - bounds.left,
            width / 2 + 8,
            Math.max(width / 2 + 8, bounds.width - width / 2 - 8)
        )
        const top = event.clientY - bounds.top - 14

        readout.style.left = `${x}px`
        /* Flip below the pointer when there is no room above, so the readout
           never gets clipped by the top of the panel. */
        readout.style.top = `${top - height < 4 ? top + height + 34 : top}px`
    }, [])

    const activePoint =
        activeIndex === null ? undefined : points[activeIndex]

    /* Only the two metrics the guest reports live get a live readout; the
       statistics endpoint is the only source for disk and network, so those
       rails show the newest sample it returned rather than implying a
       freshness the data does not have. */
    const newest = points.at(-1)

    const railValue = (key: string, live: boolean) => {
        if (live) {
            if (isUnknown) return undefined
            if (!state) return undefined
            return key === 'cpu' ? state.cpuUsed * 100 : state.memoryUsed
        }

        const value = newest?.[key]
        return typeof value === 'number' ? Math.abs(value) : undefined
    }

    if (isError) {
        return (
            <Card className='p-6'>
                <SimpleEmptyState
                    title='Resource usage is unavailable'
                    description='The node did not return statistics for this server. It may be unreachable.'
                />
            </Card>
        )
    }

    return (
        <Card
            ref={panelRef}
            /* `overflow-visible` so the readout can sit above the top row
               without being clipped by the card's own rounding. */
            className='relative overflow-visible'
            onMouseMove={handleMouseMove}
        >
            <div className='grid @3xl:grid-cols-[13rem_minmax(0,1fr)]'>
                {METRICS.map((metric, index) => {
                    const live = metric.fromState !== undefined
                    const keys = seriesKeys(metric)

                    return (
                        <MetricRow
                            key={metric.key}
                            metric={metric}
                            points={points}
                            server={server}
                            metrics={metrics}
                            live={live}
                            railValue={railValue(keys[0], live)}
                            isLoading={isPending}
                            isLast={index === METRICS.length - 1}
                            xTickFormatter={xTickFormatter}
                            onActiveIndex={setActiveIndex}
                        />
                    )
                })}
            </div>

            <PanelReadout
                ref={readoutRef}
                point={activePoint}
                formatStamp={xTickFormatter}
            />
        </Card>
    )
}

export default ResourcePanel
