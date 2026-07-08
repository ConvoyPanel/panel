import { createLazyFileRoute } from '@tanstack/react-router'

import IsoLibraryCard from '@/features/servers/media/components/IsoLibraryCard.tsx'

import Heading from '@/components/ui/Typography/Heading.tsx'

export const Route = createLazyFileRoute(
    '/_app/servers/$serverUuid/iso-library'
)({
    component: ServerIsoLibrary,
    // @ts-ignore
    meta: () => [{ title: 'ISO Library' }],
})

function ServerIsoLibrary() {
    const { serverUuid } = Route.useParams()

    return (
        <>
            <Heading>ISO Library</Heading>
            <IsoLibraryCard uuid={serverUuid} />
        </>
    )
}
