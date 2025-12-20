import { createLazyFileRoute } from '@tanstack/react-router'

import { Heading } from '@/components/ui/Typography'
import useServerSWR from '@/api/servers/use-server-swr'
import OSSelectionForm from '@/components/interfaces/Client/Server/Rebuild/OSSelectionForm'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/Card'

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/rebuild')({
    component: RebuildServerPage,
})

function RebuildServerPage() {
    const { serverUuid } = Route.useParams()
    const { data: server } = useServerSWR(serverUuid)

    return (
        <div className='flex flex-col gap-y-6'>
            <Heading>Rebuild Server</Heading>

            <Card className='max-w-xl'>
                <CardHeader>
                    <CardTitle>Operating System Selection</CardTitle>
                    <CardDescription>
                        Choose an operating system to reinstall on your server.
                        All data on the current disk will be lost.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <OSSelectionForm server={server!} />
                </CardContent>
            </Card>
        </div>
    )
}
