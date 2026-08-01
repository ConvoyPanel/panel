import type { TimeRange } from '@/features/servers/detail/api.ts'
import { useCallback, useState } from 'react'

const useTimeRange = (initial: TimeRange = 'hour') => {
    const [from, setFrom] = useState<TimeRange>(initial)

    /** Axis ticks. Terse -- these repeat across the width of the plot. */
    const XTickFormatter = useCallback(
        (timestamp: Date) => {
            const options: Intl.DateTimeFormatOptions =
                from === 'hour'
                    ? { hour: '2-digit', minute: '2-digit' }
                    : {
                          month: 'short',
                          day: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                      }
            return timestamp.toLocaleString([], options)
        },
        [from]
    )

    /**
     * The readout's heading. Unlike a tick this appears once and has room to be
     * unambiguous: over a week or a month, a bare "14:05" does not say which
     * day you are hovering.
     */
    const stampFormatter = useCallback(
        (timestamp: Date) =>
            timestamp.toLocaleString(
                [],
                from === 'hour'
                    ? { hour: '2-digit', minute: '2-digit' }
                    : {
                          month: 'short',
                          day: 'numeric',
                          hour: '2-digit',
                          minute: '2-digit',
                      }
            ),
        [from]
    )

    return { from, setFrom, XTickFormatter, stampFormatter }
}

export default useTimeRange
