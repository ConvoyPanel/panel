import { useCallback, useState } from 'react'

import { TimeRange } from '@/api/servers/getStatistics.ts'


const useTimeRange = (initial: TimeRange = 'hour') => {
    const [from, setFrom] = useState<TimeRange>(initial)

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

    return { from, setFrom, XTickFormatter }
}

export default useTimeRange
