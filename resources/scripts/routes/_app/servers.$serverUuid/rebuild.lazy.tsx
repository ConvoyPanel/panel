import RebuildForm from '@/features/servers/components/client/Rebuild/RebuildForm'
import { useServer } from '@/features/servers/detail/api.ts'
import { createLazyFileRoute } from '@tanstack/react-router'
import byteSize from 'byte-size'

import Skeleton from '@/components/ui/Skeleton'
import { Heading } from '@/components/ui/Typography'

const RebuildServerPage = () => {
    const { serverUuid } = Route.useParams()
    const { data: server } = useServer(serverUuid)

    return (
        // Capped for the same reason nodes.$nodeId/settings caps its stacked
        // sections: AppLayout gives the page up to 1600px, and a form stretched
        // that far pulls every label away from its own control. The heading
        // shares the column so it lines up with the cards.
        <div
            className={'mx-auto flex w-full max-w-3xl flex-col gap-2 @md:gap-4'}
        >
            <div>
                <Heading>
                    {server ? `Rebuild ${server.name}` : 'Rebuild Server'}
                </Heading>
                <p className={'text-muted-foreground mt-1.5 text-sm'}>
                    Install a fresh operating system. Everything on the server's{' '}
                    {server
                        ? byteSize(server.disk, { units: 'iec' }).toString()
                        : ''}{' '}
                    disk is erased; backups, ISOs and network settings are kept.
                </p>
            </div>

            {server ? (
                <RebuildForm server={server} />
            ) : (
                // One skeleton per card, at roughly the height each settles at.
                <div className={'grid grid-cols-1 gap-2 @md:gap-4'}>
                    <Skeleton className={'h-64'} />
                    <Skeleton className={'h-44'} />
                    <Skeleton className={'h-72'} />
                </div>
            )}
        </div>
    )
}

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/rebuild')({
    component: RebuildServerPage,
    // @ts-ignore
    meta: () => [{ title: 'Rebuild' }],
})
