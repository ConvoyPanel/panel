import { useEffect, useRef } from 'react'

type FrameCallback = (now: number) => void

const callbacks = new Set<FrameCallback>()
let handle: number | null = null

const tick = (now: number) => {
    callbacks.forEach(callback => callback(now))
    handle = callbacks.size > 0 ? requestAnimationFrame(tick) : null
}

/**
 * Run `callback` once per animation frame, on a loop shared by every caller.
 *
 * The resource panel has a tweened readout and a scrolling sparkline per
 * metric; giving each its own `requestAnimationFrame` would mean eight loops
 * competing to lay out the same page. One loop, one layout pass.
 *
 * The callback is held in a ref and re-read each frame, so a component may
 * close over fresh props without resubscribing (and without restarting the
 * loop) on every render. Pass `active: false` to drop out of the loop -- the
 * loop stops entirely once nothing is subscribed.
 */
const useAnimationFrame = (callback: FrameCallback, active = true) => {
    const latest = useRef(callback)
    latest.current = callback

    useEffect(() => {
        if (!active) return

        const run: FrameCallback = now => latest.current(now)
        callbacks.add(run)

        if (handle === null) {
            handle = requestAnimationFrame(tick)
        }

        return () => {
            callbacks.delete(run)

            if (callbacks.size === 0 && handle !== null) {
                cancelAnimationFrame(handle)
                handle = null
            }
        }
    }, [active])
}

export default useAnimationFrame
