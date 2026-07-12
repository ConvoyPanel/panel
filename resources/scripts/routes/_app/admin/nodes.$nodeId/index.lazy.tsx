import NodeOverview from '@/features/nodes/components/Overview/NodeOverview.tsx'
import { createLazyFileRoute } from '@tanstack/react-router'

export const Route = createLazyFileRoute('/_app/admin/nodes/$nodeId/')({
    component: NodeOverviewRoute,
})

function NodeOverviewRoute() {
    const { nodeId } = Route.useParams()

    return <NodeOverview nodeId={Number(nodeId)} />
}
