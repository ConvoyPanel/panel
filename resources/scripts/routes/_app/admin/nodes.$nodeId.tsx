import { preloadNode, useNode } from '@/features/nodes/api.ts'
import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import {
    IconDatabase,
    IconLayoutGrid,
    IconMapPins,
    IconServer,
    IconSettings,
    IconWifi,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/admin/nodes/$nodeId')({
    loader: ({ params: { nodeId } }) =>
        preloadNode(Number(nodeId)).catch(processAxiosError),
    component: NodeLayout,
    staticData: {
        title: 'Dashboard',
    },
})

function NodeLayout() {
    const { nodeId } = Route.useParams()
    const { data: node } = useNode(Number(nodeId))
    useTitle(node?.displayName)

    const nav: SidebarNav = {
        key: `admin-node:${nodeId}`,
        back: { label: 'Nodes', to: '/admin/nodes' },
        context: {
            title: node?.displayName ?? 'Node',
            subtitle: node?.fqdn,
            icon: IconServer,
        },
        groups: [
            {
                items: [
                    {
                        icon: IconLayoutGrid,
                        label: 'Overview',
                        path: `/admin/nodes/${nodeId}`,
                        activeOptions: {
                            exact: true,
                        },
                    },
                    {
                        icon: IconServer,
                        label: 'Servers',
                        path: `/admin/nodes/${nodeId}/servers`,
                    },
                ],
            },
            {
                label: 'Resources',
                items: [
                    {
                        icon: IconDatabase,
                        label: 'Storages',
                        path: `/admin/nodes/${nodeId}/storages`,
                    },
                    {
                        icon: IconWifi,
                        label: 'Network',
                        path: `/admin/nodes/${nodeId}/network`,
                    },
                    {
                        icon: IconMapPins,
                        label: 'IPAM',
                        path: `/admin/nodes/${nodeId}/ipam`,
                    },
                ],
            },
            {
                label: 'Configuration',
                items: [
                    {
                        icon: IconSettings,
                        label: 'Settings',
                        path: `/admin/nodes/${nodeId}/settings`,
                    },
                ],
            },
        ],
    }

    return (
        <AppLayout routes={nav}>
            <Outlet />
        </AppLayout>
    )
}
