import { createLazyFileRoute } from '@tanstack/react-router'

import BandwidthUsageCard from '@/features/servers/components/client/Overview/BandwidthUsageCard.tsx'
import Header from '@/features/servers/components/client/Overview/Header.tsx'
import IpamCard from '@/features/servers/components/client/Overview/IpamCard.tsx'
import SpecificationsCard from '@/features/servers/components/client/Overview/SpecificationsCard.tsx'
import Statistics from '@/features/servers/components/client/Overview/Statistics.tsx'
import StorageUsageCard from '@/features/servers/components/client/Overview/StorageUsageCard.tsx'


export const Route = createLazyFileRoute('/_app/servers/$serverUuid/')({
    component: ServerOverview,
})

function ServerOverview() {
    return (
        <>
            <Header />
            <Statistics />
            <div className={'grid grid-cols-2 gap-2 @md:grid-cols-4 @md:gap-4'}>
                <BandwidthUsageCard />
                <StorageUsageCard />
                <SpecificationsCard />
                <IpamCard />
            </div>
        </>
    )
}
