import MetricRail from '@/features/servers/components/client/Graphs/MetricRail.tsx'
import MetricRow from '@/features/servers/components/client/Graphs/MetricRow.tsx'
import {
    METRICS,
    type PanelPoint,
    seriesKeys,
} from '@/features/servers/components/client/Graphs/metrics.ts'
import {
    type TimeRange,
    useServer,
    useServerStatistics,
} from '@/features/servers/detail/api.ts'
import useLiveMetrics from '@/features/servers/hooks/use-live-metrics.ts'
import { IconChartAreaLine } from '@tabler/icons-react'
import { useMemo } from 'react'

import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'

interface Props {
    from: TimeRange
    /** Formats a timestamp for the x axis at the current range. */
    xTickFormatter: (value: Date) => string
    /** Formats the readout's heading, which has room to be less terse. */
    stampFormatter: (value: Date) => string
}

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
const ResourcePanel = ({ from, xTickFormatter, stampFormatter }: Props) => {
    const { data: server } = useServer()
    const { data, isPending, isError } = useServerStatistics({ from })
    const { metrics, data: state, isUnknown } = useLiveMetrics()

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

    /* Only the two metrics the guest reports live get a live readout; the
       statistics endpoint is the only source for disk and network, so those
       rails show the newest sample it returned rather than implying a
       freshness the data does not have. */
    const newest = points[points.length - 1]

    const railValue = (key: string, live: boolean) => {
        if (live) {
            if (isUnknown) return undefined
            if (!state) return undefined
            return key === 'cpu' ? state.cpuUsed * 100 : state.memoryUsed
        }

        const value = newest?.[key]
        return typeof value === 'number' ? Math.abs(value) : undefined
    }

    /**
     * Whether we already know no figure is coming, as opposed to still
     * waiting for one.
     *
     * This is the difference between an em dash and a skeleton, and getting it
     * wrong is not cosmetic: `useServerState` latches its failure precisely
     * because it refetches every 50ms, so `isError` is only ever true for the
     * sliver between one retry cycle failing and the next starting. A rail
     * keyed on "no value yet" alone would pulse a loading skeleton forever
     * against an unreachable node -- reading as "still coming" for something
     * that never arrives.
     *
     * Disk and network read the statistics query instead, which settles: once
     * it is no longer pending and still has no sample, none is coming --
     * whether it failed or answered with an empty window.
     */
    const railUnavailable = (live: boolean) => (live ? isUnknown : !isPending)

    /*
     * A node that answers with no samples is not a failure. Proxmox serves
     * these plots out of the guest's RRD, which is empty until it has been
     * running long enough to be rolled up -- so a freshly created server
     * legitimately has nothing to show for a while. Four blank axes leave the
     * reader wondering whether the page is broken; say so instead.
     */
    const isEmpty = !isPending && !isError && points.length === 0

    /*
     * A statistics failure is scoped to the plots, not the card.
     *
     * The rail reads live guest state and the plots read the statistics
     * endpoint; those fail independently, and a node whose RRD is unreachable
     * can still be running a guest that answers for CPU and memory. Replacing
     * the whole panel would throw away readings we have.
     */
    if (isError || isEmpty) {
        return (
            <Card className='overflow-hidden'>
                <div className='grid @3xl:grid-cols-[13rem_minmax(0,1fr)]'>
                    <div className='flex flex-col'>
                        {METRICS.map((metric, index) => {
                            const live = metric.fromState !== undefined

                            return (
                                <MetricRail
                                    key={metric.key}
                                    metric={metric}
                                    server={server}
                                    metrics={metrics}
                                    live={live}
                                    values={seriesKeys(metric).map(key =>
                                        railValue(key, live)
                                    )}
                                    unavailable={railUnavailable(live)}
                                    sampledAt={
                                        live ? undefined : newest?.timestamp
                                    }
                                    isFirst={index === 0}
                                    isLast={index === METRICS.length - 1}
                                />
                            )
                        })}
                    </div>
                    <SimpleEmptyState
                        icon={IconChartAreaLine}
                        title={
                            isError
                                ? 'History is unavailable'
                                : 'No history yet'
                        }
                        /* No "below"/"beside" — the rail is left of this at
                           wide widths and above it once the grid collapses. */
                        description={
                            isError
                                ? 'The node did not return usage statistics for this server. Live CPU and memory readings are unaffected.'
                                : 'This server has not been running long enough for the node to have recorded usage over this period. Try a shorter range.'
                        }
                        className='p-6'
                    />
                </div>
            </Card>
        )
    }

    return (
        <Card
            /* Not clipped: the readout opens past the row it belongs to, and
               on the bottom row past the card itself. The accent stripes stop
               short of the corner radius instead of relying on clipping. */
            className='relative overflow-visible'
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
                            railValues={keys.map(key => railValue(key, live))}
                            railUnavailable={railUnavailable(live)}
                            sampledAt={live ? undefined : newest?.timestamp}
                            isFirst={index === 0}
                            isLoading={isPending}
                            isLast={index === METRICS.length - 1}
                            xTickFormatter={xTickFormatter}
                            stampFormatter={stampFormatter}
                        />
                    )
                })}
            </div>
        </Card>
    )
}

export default ResourcePanel
