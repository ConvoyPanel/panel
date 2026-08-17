import AttachedMediaCard from '@/features/servers/storage/components/AttachedMediaCard.tsx'
import DevicesCard from '@/features/servers/storage/components/DevicesCard.tsx'
import StorageUsageCard from '@/features/servers/storage/components/StorageUsageCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

import Heading from '@/components/ui/Typography/Heading.tsx'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/storage')({
    component: ServerStorage,
    // @ts-ignore
    meta: () => [{ title: 'Storage' }],
})

/**
 * What storage this server has, how full it is, and what it boots from — in
 * that order, because the last two are both facts about the first.
 */
function ServerStorage() {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>Storage</Heading>
            <DevicesCard uuid={serverUuid} />
            <div className={'grid grid-cols-1 gap-2 @md:grid-cols-2 @md:gap-4'}>
                <StorageUsageCard uuid={serverUuid} />
                <AttachedMediaCard uuid={serverUuid} />
            </div>
        </>
    )
}
