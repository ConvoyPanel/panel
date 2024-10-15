import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/rebuild')({
    component: RebuildServerPage,
    // @ts-ignore
    meta: () => [{ title: 'Rebuild' }],
})

function RebuildServerPage() {
    return (
        <>
            <Heading>Rebuild Server</Heading>
        </>
    )
}
