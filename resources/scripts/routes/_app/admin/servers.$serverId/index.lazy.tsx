import { createLazyFileRoute } from '@tanstack/react-router'

import ServerDetailOverview from '@/features/servers/components/admin/detail/ServerDetailOverview.tsx'

export const Route = createLazyFileRoute('/_app/admin/servers/$serverId/')({
    component: ServerDetailIndex,
})

function ServerDetailIndex() {
    const { serverId } = Route.useParams()

    return <ServerDetailOverview serverId={Number(serverId)} />
}
