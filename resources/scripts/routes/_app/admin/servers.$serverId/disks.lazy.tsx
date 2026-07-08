import { createLazyFileRoute } from '@tanstack/react-router'

import ServerDisksPanel from '@/features/servers/components/admin/detail/ServerDisksPanel.tsx'

export const Route = createLazyFileRoute('/_app/admin/servers/$serverId/disks')({
    component: ServerDisksIndex,
})

function ServerDisksIndex() {
    const { serverId } = Route.useParams()

    return <ServerDisksPanel serverId={Number(serverId)} />
}
