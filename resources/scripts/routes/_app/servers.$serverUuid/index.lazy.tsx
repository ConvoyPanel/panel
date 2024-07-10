import { createLazyFileRoute } from '@tanstack/react-router'

import useServerSWR from '@/api/servers/use-server-swr.ts'

import { Heading } from '@/components/ui/Typography'


export const Route = createLazyFileRoute('/_app/servers/$serverUuid/')({
    component: ServerOverview,
    // @ts-ignore
    meta: () => [{ title: 'Dashboard' }],
})

function ServerOverview() {
    const { serverUuid } = Route.useParams()
    const { data: server } = useServerSWR(serverUuid)

    return (
        <>
            <Heading className={'max-w-md truncate'}>{server?.name}</Heading>
            <p className={''}></p>
        </>
    )
}
