import { createLazyFileRoute } from '@tanstack/react-router'

import AddressesCard from '@/features/servers/networking/components/AddressesCard.tsx'
import NameserversCard from '@/features/servers/networking/components/NameserversCard.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'

export const Route = createLazyFileRoute(
    '/_app/servers/$serverUuid/networking'
)({
    component: ServerNetworking,
    // @ts-ignore
    meta: () => [{ title: 'Networking' }],
})

function ServerNetworking() {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>Networking</Heading>
            <div className={'grid grid-cols-1 gap-5 @md:grid-cols-2'}>
                <AddressesCard uuid={serverUuid} />
                <NameserversCard uuid={serverUuid} />
            </div>
        </>
    )
}
