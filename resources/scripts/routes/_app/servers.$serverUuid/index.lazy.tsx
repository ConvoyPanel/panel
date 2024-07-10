import { createLazyFileRoute } from '@tanstack/react-router'

import Header from '@/components/interfaces/Server/Overview/Header.tsx'
import Statistics from '@/components/interfaces/Server/Overview/Statistics.tsx'


export const Route = createLazyFileRoute('/_app/servers/$serverUuid/')({
    component: ServerOverview,
    // @ts-ignore
    meta: () => [{ title: 'Dashboard' }],
})

function ServerOverview() {
    return (
        <>
            <Header />
            <Statistics />
        </>
    )
}
