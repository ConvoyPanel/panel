import { createLazyFileRoute } from '@tanstack/react-router'

import HistoricalCpuUsageCard from '@/features/servers/components/client/Graphs/HistoricalCpuUsageCard.tsx'
import HistoricalDiskUsageCard from '@/features/servers/components/client/Graphs/HistoricalDiskUsageCard.tsx'
import HistoricalMemoryUsageCard from '@/features/servers/components/client/Graphs/HistoricalMemoryUsageCard.tsx'
import HistoricalNetworkUsageCard from '@/features/servers/components/client/Graphs/HistoricalNetworkUsageCard.tsx'
import LiveCpuUsageCard from '@/features/servers/components/client/Graphs/LiveCpuUsageCard.tsx'
import LiveMemoryUsageCard from '@/features/servers/components/client/Graphs/LiveMemoryUsageCard.tsx'
import Statistics from '@/features/servers/components/client/Overview/Statistics.tsx'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/graphs')({
    component: ServerGraphs,
    // @ts-ignore
    meta: () => [{ title: 'Graphs' }],
})

function ServerGraphs() {
    return (
        <>
            {/* Its own heading, not Overview's server-identity Header — the
                sidebar already carries the server context. */}
            <Heading>Resource usage</Heading>
            <Statistics />
            <div className={'grid grid-cols-1 gap-2 @md:grid-cols-4 @md:gap-4'}>
                <LiveCpuUsageCard />
                <LiveMemoryUsageCard />
                <HistoricalCpuUsageCard />
                <HistoricalMemoryUsageCard />
                <HistoricalDiskUsageCard />
                <HistoricalNetworkUsageCard />
            </div>
        </>
    )
}
