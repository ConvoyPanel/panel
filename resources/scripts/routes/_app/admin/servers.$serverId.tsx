import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import { IconDatabase, IconLayoutGrid } from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import { preloadServer, useServer } from '@/features/servers/admin/api.ts'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { Route as RouteDef } from '@/components/ui/Navigation/Navigation.types.ts'

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

    const routes: RouteDef[] = [
        {
            icon: IconLayoutGrid,
            label: 'Overview',
            path: `/admin/servers/${serverId}`,
            activeOptions: {
                exact: true,
            },
        },
        {
            icon: IconDatabase,
            label: 'Disks',
            path: `/admin/servers/${serverId}/disks`,
        },
    ]

    return (
        <AppLayout routes={routes}>
            <Outlet />
        </AppLayout>
    )
}
