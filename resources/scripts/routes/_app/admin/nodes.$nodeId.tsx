import useTitle from '@/hooks/use-title.ts'
import {
    IconDatabase,
    IconLayoutGrid,
    IconMapPin,
    IconSettings,
    IconWifi,
} from '@tabler/icons-react'
import { createFileRoute, Outlet } from '@tanstack/react-router'

import useNodeSWR, { preloadNode } from '@/api/admin/nodes/use-node-swr.ts'

import { Route as RouteDef } from '@/components/ui/Navigation/Navigation.types.ts'
import AppLayout from '@/components/layouts/AppLayout.tsx'
import { processAxiosError } from '@/utils/http.ts'

export const Route = createFileRoute('/_app/admin/nodes/$nodeId')({
    loader: ({ params: { nodeId }}) =>
        preloadNode(Number(nodeId)).catch(processAxiosError),
    component: NodeLayout,
    staticData: {
        title: 'Dashboard',
    }
})

function NodeLayout() {
    const { nodeId } = Route.useParams()
    const { data: node } = useNodeSWR(nodeId)
    useTitle(node?.displayName)

    const routes: RouteDef[] = [
        {
            icon: IconLayoutGrid,
            label: 'Overview',
            path: `/admin/nodes/${nodeId}`,
            activeOptions: {
                exact: true,
            },
        },
        {
            icon: IconDatabase,
            label: 'Disks',
            path: `/admin/nodes/${nodeId}/disks`,
        },
        {
            icon: IconWifi,
            label: 'Network',
            path: `/admin/nodes/${nodeId}/network`,
        },
        {
            icon: IconMapPin,
            label: 'IPAM',
            path: `/admin/nodes/${nodeId}/ipam`,
        },
        {
            icon: IconSettings,
            label: 'Settings',
            path: `/admin/nodes/${nodeId}/settings`,
        },
    ]

    return <AppLayout routes={routes}>
        <Outlet />
    </AppLayout>
}
