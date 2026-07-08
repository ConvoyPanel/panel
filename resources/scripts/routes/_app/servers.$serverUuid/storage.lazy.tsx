import { createLazyFileRoute } from '@tanstack/react-router'

import BootOrderCard from '@/features/servers/storage/components/BootOrderCard.tsx'
import StorageUsageCard from '@/features/servers/storage/components/StorageUsageCard.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/storage')({
    component: ServerStorage,
    // @ts-ignore
    meta: () => [{ title: 'Storage' }],
})

function ServerStorage() {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>Storage</Heading>
            <div className={'grid grid-cols-1 gap-5 @md:grid-cols-2'}>
                <StorageUsageCard uuid={serverUuid} />
                <BootOrderCard uuid={serverUuid} />
            </div>
        </>
    )
}
