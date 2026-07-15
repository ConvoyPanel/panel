import { useEffect, useRef, useState } from 'react'

interface Options {
    /** Hold off this long before showing. Work that finishes first shows nothing. */
    delay?: number
    /** Once shown, keep it up at least this long. */
    minDuration?: number
}

/**
 * Gates a spinner so it only appears for work that is actually slow.
 *
 * A spinner that renders and vanishes inside a couple hundred milliseconds
 * reads as a glitch rather than as progress, so `delay` suppresses it entirely
 * for fast work, and `minDuration` stops it flashing when work lands just past
 * that threshold.
 *
 * This governs the *spinner* only. Keep whatever disables the control bound to
 * the real loading flag, so a click is still acknowledged instantly and cannot
 * be fired twice while the spinner is being withheld.
 */
const useDelayedLoading = (
    loading: boolean,
    { delay = 350, minDuration = 500 }: Options = {}
) => {
    const [visible, setVisible] = useState(false)
    const shownAt = useRef<number | null>(null)

    useEffect(() => {
        if (loading) {
            const timer = setTimeout(() => {
                shownAt.current = Date.now()
                setVisible(true)
            }, delay)

            return () => clearTimeout(timer)
        }

        // Finished before the spinner was ever shown — nothing to hold open.
        if (shownAt.current === null) {
            setVisible(false)

            return
        }

        const remaining = minDuration - (Date.now() - shownAt.current)
        const timer = setTimeout(
            () => {
                shownAt.current = null
                setVisible(false)
            },
            Math.max(0, remaining)
        )

        return () => clearTimeout(timer)
    }, [loading, delay, minDuration])

    return visible
}

export default useDelayedLoading
