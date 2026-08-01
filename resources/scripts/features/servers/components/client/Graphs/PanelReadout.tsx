import type { PanelPoint } from '@/features/servers/components/client/Graphs/MetricRow.tsx'
import {
    METRICS,
    seriesKeys,
    seriesLabels,
} from '@/features/servers/components/client/Graphs/metrics.ts'
import { cn } from '@/utils'
import { forwardRef } from 'react'

interface Props {
    point: PanelPoint | undefined
    /** Formats the point's timestamp for the range currently shown. */
    formatStamp: (value: Date) => string
}

/**
 * One readout for the whole panel.
 *
 * Four stacked plots given four tooltips is worse than it sounds: each box
 * opens over the row above it, hiding the trace you are trying to compare
 * against. And it misses the point of stacking them, which is that every metric
 * answers for the same instant -- so they belong in one list, read top to
 * bottom, in the same order as the rows.
 *
 * Positioned by the panel, which follows the pointer without re-rendering.
 */
const PanelReadout = forwardRef<HTMLDivElement, Props>(
    ({ point, formatStamp }, ref) => (
        <div
            ref={ref}
            role='status'
            aria-live='off'
            className={cn(
                'pointer-events-none absolute z-20 hidden -translate-x-1/2 -translate-y-full',
                'rounded-lg bg-background px-2.5 py-2 text-xs shadow-xl ring-1 ring-foreground/10'
            )}
        >
            {point && (
                <>
                    <div className='mb-1.5 font-medium text-muted-foreground'>
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
                                    <div
                                        key={key}
                                        className='flex items-center gap-2'
                                    >
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
                </>
            )}
        </div>
    )
)
PanelReadout.displayName = 'PanelReadout'

export default PanelReadout
