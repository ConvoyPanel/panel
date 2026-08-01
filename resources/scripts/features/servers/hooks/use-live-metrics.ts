import { serverQueries, useServerState } from '@/features/servers/detail/api.ts'
import type { ServerStateData } from '@/types/server'
import { useParams } from '@tanstack/react-router'
import { useEffect, useMemo } from 'react'

import { queryClient } from '@/lib/query-client.ts'

/** Seconds of live history the traces hold. */
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

/**
 * One entry per server, outliving the components that read it.
 *
 * The buffers used to sit in a `useRef`, so every consumer kept its own: the
 * two overview tiles sampled independently of each other and of the graphs
 * page, and moving between those pages threw the history away and restarted
 * from an empty trace. There is nothing page-specific about what a guest has
 * been doing for the last minute, so it is collected once, here.
 *
 * Not a zustand store, despite the shape. Nothing re-renders on this: consumers
 * read the buffers from an animation frame and write straight to the DOM, so
 * what a store would add is subscription, and there is nothing to subscribe.
 */
interface Entry {
    metrics: LiveMetrics
    /** Consumers currently mounted. */
    readers: number
    sampler: number | null
    /** Pending teardown, armed once the last reader leaves. */
    reaper: number | null
}

const entries = new Map<string, Entry>()

const emptyMetrics = (): LiveMetrics => ({
    buffers: {
        cpu: new Array(LIVE_WINDOW).fill(0),
        memory: new Array(LIVE_WINDOW).fill(0),
    },
    lastSampleAt: performance.now(),
    intervalMs: LIVE_SAMPLE_MS,
    filled: 0,
})

const entryFor = (uuid: string): Entry => {
    let entry = entries.get(uuid)

    if (!entry) {
        entry = {
            metrics: emptyMetrics(),
            readers: 0,
            sampler: null,
            reaper: null,
        }
        entries.set(uuid, entry)
    }

    return entry
}

/**
 * Sampling reads the query cache rather than a component's copy of the result,
 * so it does not matter which consumer -- if any -- happens to be mounted.
 */
const sample = (uuid: string, entry: Entry) => {
    const reading = queryClient.getQueryData<ServerStateData>(
        serverQueries.state(uuid).queryKey
    )

    if (!reading) return

    const next: Record<LiveMetricKey, number> = {
        cpu: (reading.cpuUsed ?? 0) * 100,
        memory: reading.memoryUsed ?? 0,
    }

    for (const key of Object.keys(next) as LiveMetricKey[]) {
        const buffer = entry.metrics.buffers[key]
        buffer.shift()
        buffer.push(next[key])
    }

    entry.metrics.filled = Math.min(entry.metrics.filled + 1, LIVE_WINDOW)
    entry.metrics.lastSampleAt = performance.now()
}

const acquire = (uuid: string) => {
    const entry = entryFor(uuid)
    entry.readers++

    if (entry.reaper !== null) {
        window.clearTimeout(entry.reaper)
        entry.reaper = null
    }

    if (entry.sampler === null) {
        sample(uuid, entry)
        entry.sampler = window.setInterval(
            () => sample(uuid, entry),
            LIVE_SAMPLE_MS
        )
    }

    return () => {
        entry.readers--
        if (entry.readers > 0) return

        /*
         * Keep sampling for a window's worth of time after the last reader
         * leaves, then throw the entry away.
         *
         * A route change unmounts the old page before mounting the new one, so
         * tearing down the moment the count hits zero would stop the clock on
         * every navigation -- the reset this exists to avoid. Holding on for
         * exactly the length of the window also decides the other case for us:
         * stay away longer than that and every sample would have scrolled out
         * regardless, so starting clean loses nothing.
         */
        entry.reaper = window.setTimeout(() => {
            if (entry.sampler !== null) window.clearInterval(entry.sampler)
            entries.delete(uuid)
        }, LIVE_WINDOW * LIVE_SAMPLE_MS)
    }
}

/**
 * Live CPU and memory as fixed-length ring buffers, shared by every consumer
 * looking at the same server.
 *
 * The returned `metrics` object is stable and mutates in place; read it from an
 * animation frame rather than rendering off it.
 */
const useLiveMetrics = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    const query = useServerState(serverUuid)
    const metrics = useMemo(() => entryFor(serverUuid).metrics, [serverUuid])

    useEffect(() => acquire(serverUuid), [serverUuid])

    return { metrics, ...query }
}

export default useLiveMetrics
