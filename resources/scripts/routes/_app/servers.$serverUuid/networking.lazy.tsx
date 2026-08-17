import AddressesCard from '@/features/servers/networking/components/AddressesCard.tsx'
import NameserversCard from '@/features/servers/networking/components/NameserversCard.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

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
            {/* Two real columns, neither one spanning. The old grid put the
                addresses card across both and the nameservers underneath, so
                every extra IP pushed the form down the page; now that the card
                caps its own height at five rows, the two fit side by side and
                growth inside the left card moves nothing at all.

                Side-by-side also spends the width AppLayout gives us (up to
                1600px) on content rather than on margin — a single capped
                column just relocates the emptiness to the right of the cards.
                Below `@4xl` they stack, which is the same layout as before. */}
            <div
                className={
                    'grid grid-cols-1 items-start gap-2 @md:gap-4 @4xl:grid-cols-2'
                }
            >
                <AddressesCard uuid={serverUuid} />
                <NameserversCard uuid={serverUuid} />
            </div>
        </>
    )
}
