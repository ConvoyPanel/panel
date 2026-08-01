import { useServerState } from '@/features/servers/detail/api.ts'
import { useEffect, useRef } from 'react'

/** Seconds of live history the rail sparklines hold. */
export const LIVE_WINDOW = 60

/**
 * How often a sample is appended to the live buffers.
 *
 * Deliberately not the poll interval. `serverQueries.state` refetches every
 * 50ms, and a trace that gained a point twenty times a second would show three
 * seconds of history at a resolution nobody can read. Sampling on our own
 * cadence also means the trace advances at a steady rate no matter how the
 * requests actually land -- which is what lets the sparkline scroll smoothly
 * rather than lurch whenever a response is slow.
 */
export const LIVE_SAMPLE_MS = 1000

/** Only these metrics have a live reading; the guest state endpoint carries no disk or network counters. */
export type LiveMetricKey = 'cpu' | 'memory'

export interface LiveMetrics {
    /** Ring buffers, oldest sample first. Mutated in place. */
    buffers: Record<LiveMetricKey, number[]>
    /** `performance.now()` when the newest sample was appended. */
    lastSampleAt: number
    intervalMs: number
    /**
     * How many slots hold a real reading, counting back from the newest.
     *
     * The buffer is allocated full-length but starts empty, and consumers draw
     * only this many points. Padding the rest -- with zeroes, or by repeating
     * the first reading across the window -- would draw a minute of history we
     * were not there for: a flat line asserting the value never moved, or a
     * cliff climbing out of the floor that never happened. The trace grows in
     * from the right instead, and says nothing about time it did not observe.
     */
    filled: number
}

const emptyBuffers = (): Record<LiveMetricKey, number[]> => ({
    cpu: new Array(LIVE_WINDOW).fill(0),
    memory: new Array(LIVE_WINDOW).fill(0),
})

/**
 * Live CPU and memory as fixed-length ring buffers, plus the timestamp of the
 * newest sample.
 *
 * Returned as a ref rather than state on purpose: the sparklines and readouts
 * that consume this redraw from an animation frame, and re-rendering the whole
 * panel twenty times a second to hand them numbers they are going to
 * interpolate anyway would be pure waste.
 */
const useLiveMetrics = () => {
    const query = useServerState()
    const { data: state } = query

    const metrics = useRef<LiveMetrics>({
        buffers: emptyBuffers(),
        lastSampleAt: performance.now(),
        intervalMs: LIVE_SAMPLE_MS,
        filled: 0,
    })

    /* The timer reads the latest state through a ref, so a new poll result
       doesn't tear down and restart the sampling interval. */
    const latest = useRef(state)
    latest.current = state

    useEffect(() => {
        const sample = () => {
            const reading = latest.current
            if (!reading) return

            const next: Record<LiveMetricKey, number> = {
                cpu: (reading.cpuUsed ?? 0) * 100,
                memory: reading.memoryUsed ?? 0,
            }

            const current = metrics.current

            for (const key of Object.keys(next) as LiveMetricKey[]) {
                const buffer = current.buffers[key]
                buffer.shift()
                buffer.push(next[key])
            }

            current.filled = Math.min(current.filled + 1, LIVE_WINDOW)
            current.lastSampleAt = performance.now()
        }

        sample()
        const id = window.setInterval(sample, LIVE_SAMPLE_MS)

        return () => window.clearInterval(id)
    }, [])

    return { metrics, ...query }
}

export default useLiveMetrics
