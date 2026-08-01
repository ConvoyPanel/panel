import ResourcePanel from '@/features/servers/components/client/Graphs/ResourcePanel.tsx'
import TimeRangeSelector from '@/features/servers/components/client/Graphs/TimeRangeSelector.tsx'
import useTimeRange from '@/features/servers/hooks/use-time-range.ts'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/graphs')({
    component: ServerGraphs,
    // @ts-ignore
    meta: () => [{ title: 'Graphs' }],
})

function ServerGraphs() {
    /*
     * The range lives here rather than in the charts. Each historical card used
     * to own its own `useTimeRange`, which put five selectors on one page and
     * left no way to line the plots up: setting disk to weekly left CPU on
     * hourly, and reading one against the other told you nothing.
     */
    const { from, setFrom, XTickFormatter, stampFormatter } = useTimeRange()

    return (
        <>
            {/* Its own heading, not Overview's server-identity Header — the
                sidebar already carries the server context.

                Overview's `Statistics` tiles are deliberately not repeated
                here: the panel's rail already carries live CPU and memory, and
                showing the same two figures twice, a few hundred pixels apart,
                only invites the reader to check whether they agree. */}
            <div
                className={'flex flex-wrap items-center justify-between gap-3'}
            >
                <Heading>Resource usage</Heading>
                <TimeRangeSelector from={from} setFrom={setFrom} />
            </div>
            <ResourcePanel
                from={from}
                xTickFormatter={XTickFormatter}
                stampFormatter={stampFormatter}
            />
        </>
    )
}
