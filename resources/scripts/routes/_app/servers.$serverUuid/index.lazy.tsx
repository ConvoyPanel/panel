import BandwidthUsageCard from '@/features/servers/components/client/Overview/BandwidthUsageCard.tsx'
import Header from '@/features/servers/components/client/Overview/Header.tsx'
import IpamCard from '@/features/servers/components/client/Overview/IpamCard.tsx'
import SpecificationsCard from '@/features/servers/components/client/Overview/SpecificationsCard.tsx'
import Statistics from '@/features/servers/components/client/Overview/Statistics.tsx'
import StorageUsageCard from '@/features/servers/components/client/Overview/StorageUsageCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/')({
    component: ServerOverview,
})

function ServerOverview() {
    return (
        <>
            <Header />
            <Statistics />
            {/* Same 4-up threshold as Statistics above -- the two rows must
                break together or they stop reading as one stack. IpamCard's
                col-span must move with it: a span-4 in a 2-col grid would add
                implicit columns and overflow the row. */}
            <div
                className={'grid grid-cols-2 gap-2 @md:gap-4 @5xl:grid-cols-4'}
            >
                <BandwidthUsageCard />
                <StorageUsageCard />
                <SpecificationsCard />
                <IpamCard />
            </div>
        </>
    )
}
