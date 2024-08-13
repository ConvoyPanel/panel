import { createLazyFileRoute } from '@tanstack/react-router'

import BandwidthUsageCard from '@/components/interfaces/Client/Server/Overview/BandwidthUsageCard.tsx'
import Header from '@/components/interfaces/Client/Server/Overview/Header.tsx'
import IpamCard from '@/components/interfaces/Client/Server/Overview/IpamCard.tsx'
import SpecificationsCard from '@/components/interfaces/Client/Server/Overview/SpecificationsCard.tsx'
import Statistics from '@/components/interfaces/Client/Server/Overview/Statistics.tsx'
import StorageUsageCard from '@/components/interfaces/Client/Server/Overview/StorageUsageCard.tsx'


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
