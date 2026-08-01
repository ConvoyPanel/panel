import type { PanelPoint } from '@/features/servers/components/client/Graphs/MetricRow.tsx'
import {
    METRICS,
    seriesKeys,
    seriesLabels,
} from '@/features/servers/components/client/Graphs/metrics.ts'

interface Props {
    /** Formats the point's timestamp for the range currently shown. */
    formatStamp: (value: Date) => string
    /**
     * Render nothing. Every row mounts this so Recharts always sees the same
     * content element -- only the row under the pointer actually draws a box.
     */
    silent?: boolean
    /* Injected by Recharts when it clones this into a chart's `content`. */
    active?: boolean
    payload?: { payload?: PanelPoint }[]
}

/**
 * One readout for the whole panel.
 *
 * Four stacked plots given four tooltips is worse than it sounds: each box
 * opens over the row above it, hiding the trace you were trying to compare
 * against. It also misses the point of stacking them, which is that every
 * metric answers for the same instant -- so they belong in one list, read top
 * to bottom in the same order as the rows.
 *
 * Only the row under the pointer renders this; the others contribute their
 * cursor line and nothing else. Since all four rows plot the same array,
 * Recharts hands us the entire point -- every metric, already at the hovered
 * instant -- so there is no index to look up or keep in sync.
 */
const PanelReadout = ({ formatStamp, silent, active, payload }: Props) => {
    const point = payload?.[0]?.payload

    if (silent || !active || !point) return null

    return (
        <div className='bg-background ring-foreground/10 rounded-lg px-2.5 py-2 text-xs shadow-xl ring-1'>
            <div className='text-muted-foreground mb-1.5 font-medium'>
                {formatStamp(point.timestamp)}
            </div>
            <div className='grid gap-1'>
                {METRICS.map(metric => {
                    const keys = seriesKeys(metric)
                    const labels = seriesLabels(metric)

                    return keys.map((key, index) => {
                        const value = point[key]
                        if (typeof value !== 'number') return null

                        return (
                            <div key={key} className='flex items-center gap-2'>
                                <i
                                    aria-hidden
                                    className='block size-2 shrink-0 rounded-[2px]'
                                    style={{
                                        background: metric.color,
                                        opacity: index === 0 ? 1 : 0.45,
                                    }}
                                />
                                <span className='text-muted-foreground'>
                                    {keys.length > 1
                                        ? `${metric.name} ${labels[index]}`
                                        : metric.name}
                                </span>
                                <span className='ml-auto pl-6 font-mono font-medium tabular-nums'>
                                    {metric.format(value)}
                                </span>
                            </div>
                        )
                    })
                })}
            </div>
        </div>
    )
}

export default PanelReadout
