import { createLazyFileRoute } from '@tanstack/react-router'

import ISOLibraryCard from '@/features/servers/media/components/ISOLibraryCard.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'

export const Route = createLazyFileRoute(
    '/_app/servers/$serverUuid/iso-library'
)({
    component: ServerISOLibrary,
    // @ts-ignore
    meta: () => [{ title: 'ISO Library' }],
})

function ServerISOLibrary() {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>ISO Library</Heading>
            <ISOLibraryCard uuid={serverUuid} />
        </>
    )
}
