import type {
    LiveMetricKey,
    LiveMetrics,
} from '@/features/servers/hooks/use-live-metrics.ts'
import useAnimationFrame from '@/hooks/use-animation-frame.ts'
import useMediaQuery from '@/hooks/use-media-query.ts'
import { cn } from '@/utils'
import { useId, useRef } from 'react'

/* The plot is drawn on a fixed viewBox with preserveAspectRatio="none", so it
   stretches to whatever width the rail gives it without measuring anything.
   Strokes carry vector-effect="non-scaling-stroke" so the line keeps its
   weight under that distortion. */
const VIEW_W = 1000
const VIEW_H = 100

interface Props {
    /** The shared per-server buffers. Stable object, mutated in place. */
    metrics: LiveMetrics
    series: LiveMetricKey
    color: string
    /** Fixed axis maximum. Without one the axis follows the data. */
    ceiling?: number
    className?: string
}

const clamp = (value: number, min: number, max: number) =>
    value < min ? min : value > max ? max : value

/**
 * A live trace that scrolls at a constant velocity.
 *
 * The reason the old live charts lurched is that they moved the data and left
 * the geometry alone: every sample shifted the array by one slot, so the whole
 * trace jumped a slot in a single frame. Here the sample rate and the motion
 * are separated -- each frame draws the buffer at
 *
 *     x = (i - progress) * step
 *
 * where `progress` is how far we are through the current sample interval. The
 * trace travels one slot *over* the interval instead of jumping one slot *on
 * arrival*, so a new reading changes the shape at the right-hand edge and
 * nothing else moves. Recharts cannot express this -- it re-lays out the
 * series on each render -- which is why this is hand-drawn SVG.
 *
 * Nothing here re-renders: the path data is written straight to the DOM from
 * the shared animation frame.
 */
const LiveSparkline = ({
    metrics,
    series,
    color,
    ceiling,
    className,
}: Props) => {
    const gradientId = useId().replace(/:/g, '')
    const lineRef = useRef<SVGPathElement>(null)
    const areaRef = useRef<SVGPathElement>(null)
    const scale = useRef<number | null>(null)

    const reduced = useMediaQuery('(prefers-reduced-motion: reduce)')

    useAnimationFrame(now => {
        const line = lineRef.current
        const area = areaRef.current
        if (!line || !area) return

        const { buffers, lastSampleAt, intervalMs, filled } = metrics
        const values = buffers[series]

        /* Two points make a line; below that there is nothing honest to draw. */
        if (filled < 2) return

        /* Reduced motion still redraws, but with no sub-interval offset: the
           trace steps forward once per sample instead of gliding. */
        const progress = reduced
            ? 0
            : clamp((now - lastSampleAt) / intervalMs, 0, 1)

        const count = values.length
        /* Only the newest `filled` slots hold a reading; the rest of the
           buffer has never been written to. */
        const first = count - filled

        let max = ceiling
        if (!max) {
            max = Math.max(...values.slice(first)) * 1.15 || 1
            /* Ease the autoscale, or a single spike snaps the whole axis and
               every earlier point appears to drop. */
            scale.current =
                scale.current === null || reduced
                    ? max
                    : scale.current + (max - scale.current) * 0.12
            max = scale.current
        }

        /* One extra slot of width: the newest sample sits just off the right
           edge and slides into view over the interval. Positions come from the
           slot's index in the whole window, so a partly-filled buffer puts its
           newest reading in exactly the same place a full one would -- the
           trace grows leftward while its right-hand edge stays put. */
        const step = VIEW_W / (count - 2)
        const x = (index: number) => (index - progress) * step
        const y = (value: number) =>
            VIEW_H - (clamp(value, 0, max) / max) * (VIEW_H - 2) - 1

        let d = ''
        for (let i = first; i < count; i++) {
            /* Straight segments, not a spline. A smoothed curve on live
               telemetry invents peaks between samples that the guest never
               reported. */
            d += `${i === first ? 'M' : 'L'}${x(i).toFixed(1)},${y(values[i]).toFixed(1)}`
        }

        line.setAttribute('d', d)
        area.setAttribute(
            'd',
            `${d}L${x(count - 1).toFixed(1)},${VIEW_H}L${x(first).toFixed(1)},${VIEW_H}Z`
        )
    })

    return (
        <svg
            viewBox={`0 0 ${VIEW_W} ${VIEW_H}`}
            preserveAspectRatio='none'
            aria-hidden='true'
            className={cn('block h-full w-full overflow-hidden', className)}
        >
            <defs>
                <linearGradient id={gradientId} x1='0' y1='0' x2='0' y2='1'>
                    <stop offset='0%' stopColor={color} stopOpacity={0.22} />
                    <stop offset='100%' stopColor={color} stopOpacity={0} />
                </linearGradient>
            </defs>
            <path ref={areaRef} fill={`url(#${gradientId})`} stroke='none' />
            <path
                ref={lineRef}
                fill='none'
                stroke={color}
                strokeWidth={1.5}
                strokeLinejoin='round'
                vectorEffect='non-scaling-stroke'
            />
        </svg>
    )
}

export default LiveSparkline
