import { createLazyFileRoute } from '@tanstack/react-router'

import SnapshotQuotaSidebar from '@/components/interfaces/Client/Server/Snapshots/SnapshotQuotaSidebar.tsx'
import SnapshotView from '@/components/interfaces/Client/Server/Snapshots/SnapshotView.tsx'

import { Heading } from '@/components/ui/Typography'


export const Route = createLazyFileRoute('/_app/servers/$serverUuid/snapshots')(
    {
        component: ServerSnapshots,
        // @ts-ignore
        meta: () => [{ title: 'Snapshots' }],
    }
)

function ServerSnapshots() {
    return (
        <>
            <Heading>Snapshots</Heading>
            <SnapshotQuotaSidebar />
            <SnapshotView />
        </>
    )
}
