import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import {
    IconChartBar,
    IconCopy,
    IconDatabase,
    IconDisc,
    IconLayoutGrid,
    IconLock,
    IconNetwork,
    IconRefresh,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'

import InstallingServer from '@/components/interfaces/Client/Server/Status/InstallingServer.tsx'
import SuspendedServer from '@/components/interfaces/Client/Server/Status/SuspendedServer.tsx'
import DeferredOSSelection from '@/components/interfaces/Client/Server/Status/DeferredOSSelection.tsx'
import useServerSWR, { preloadServer } from '@/api/servers/use-server-swr.ts'

import AppLayout from '@/components/layouts/AppLayout.tsx'

import { Route as RouteDef } from '@/components/ui/Navigation/Navigation.types.ts'

export const Route = createFileRoute('/_app/servers/$serverUuid')({
    loader: ({ params: { serverUuid } }) =>
        preloadServer(serverUuid).catch(processAxiosError),
    component: ServerLayout,
    staticData: {
        title: 'Dashboard',
    },
})

function ServerLayout() {
    const { serverUuid } = Route.useParams()
    const { data: server } = useServerSWR(serverUuid)
    useTitle(server?.name)

    const routes: RouteDef[] = [
        {
            icon: IconLayoutGrid,
            label: 'Overview',
            path: `/servers/${serverUuid}`,
            activeOptions: {
                exact: true,
            },
        },
        {
            icon: IconChartBar,
            label: 'Graphs',
            path: `/servers/${serverUuid}/graphs`,
        },
        {
            icon: IconCopy,
            label: 'Backups',
            path: `/servers/${serverUuid}/backups`,
        },
        {
            icon: IconDisc,
            label: 'ISO Library',
            path: `/servers/${serverUuid}/iso-library`,
        },
        {
            icon: IconDatabase,
            label: 'Storage',
            path: `/servers/${serverUuid}/storage`,
        },
        {
            icon: IconNetwork,
            label: 'Networking',
            path: `/servers/${serverUuid}/networking`,
        },
        {
            icon: IconLock,
            label: 'Security',
            path: `/servers/${serverUuid}/security`,
        },
        {
            icon: IconRefresh,
            label: 'Rebuild',
            path: `/servers/${serverUuid}/rebuild`,
        },
    ]

    const isInstalling =
        server?.status === 'installing' ||
        server?.status === 'install_failed' ||
        server?.status === 'restoring_backup' ||
        server?.status === 'deleting'
    const isDeferred = server?.status === 'deferred_os_selection'
    const isSuspended = server?.status === 'suspended'

    return (
        <AppLayout routes={routes}>
            {isDeferred ? (
                <DeferredOSSelection server={server} />
            ) : isInstalling ? (
                <InstallingServer server={server} />
            ) : isSuspended ? (
                <SuspendedServer />
            ) : (
                <Outlet />
            )}
        </AppLayout>
    )
}
