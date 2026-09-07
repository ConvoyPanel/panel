import Header from '@/features/servers/components/client/Overview/Header.tsx'
import IpamCard from '@/features/servers/components/client/Overview/IpamCard.tsx'
import Statistics from '@/features/servers/components/client/Overview/Statistics.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/')({
    component: ServerOverview,
})

function ServerOverview() {
    return (
        <>
            <Header />
            <Statistics />
            <IpamCard />
        </>
    )
}
