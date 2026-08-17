import { preloadServer, useServer } from '@/features/servers/admin/api.ts'
import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import {
    IconAdjustments,
    IconDatabase,
    IconLayoutDashboard,
    IconLayoutGrid,
    IconServer,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/admin/servers/$serverId')({
    loader: ({ params: { serverId } }) =>
        preloadServer(Number(serverId)).catch(processAxiosError),
    component: ServerLayout,
    staticData: {
        title: 'Server',
    },
})

function ServerLayout() {
    const { serverId } = Route.useParams()
    const { data: server } = useServer(Number(serverId))
    useTitle(server?.name)

    const nav: SidebarNav = {
        key: `admin-server:${serverId}`,
        back: { label: 'Servers', to: '/admin/servers' },
        context: {
            title: server?.name ?? 'Server',
            subtitle: server?.hostname,
            icon: IconServer,
        },
        groups: [
            {
                items: [
                    {
                        icon: IconLayoutGrid,
                        label: 'Overview',
                        path: `/admin/servers/${serverId}`,
                        activeOptions: {
                            exact: true,
                        },
                    },
                ],
            },
            {
                label: 'Storage',
                items: [
                    {
                        icon: IconDatabase,
                        label: 'Disks',
                        path: `/admin/servers/${serverId}/disks`,
                    },
                ],
            },
            {
                label: 'Configuration',
                items: [
                    {
                        icon: IconAdjustments,
                        label: 'Build & limits',
                        path: `/admin/servers/${serverId}/settings`,
                    },
                ],
            },
            // Mirror of the client sidebar's "Manage in Admin". The client route
            // is keyed by uuid, which only arrives with the loaded server. Every
            // route under /admin is root-admin only, and the server policy lets a
            // root admin through for any server, so this never dead-ends.
            ...(server
                ? [
                      {
                          label: 'Client',
                          items: [
                              {
                                  icon: IconLayoutDashboard,
                                  label: 'Client dashboard',
                                  path: `/servers/${server.uuid}`,
                              },
                          ],
                      },
                  ]
                : []),
        ],
    }

    return (
        <AppLayout routes={nav}>
            <Outlet />
        </AppLayout>
    )
}
