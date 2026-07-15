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
    IconServer,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'
import { Suspense, lazy } from 'react'

import { useServer, preloadServer } from '@/features/servers/detail/api.ts'

import AppLayout from '@/components/layouts/AppLayout.tsx'
import Spinner from '@/components/ui/Spinner.tsx'

import { SidebarNav } from '@/components/ui/Navigation/Navigation.types.ts'

const InstallingServer = lazy(
    () =>
        import(
            '@/features/servers/components/client/Status/InstallingServer.tsx'
        )
)
const SuspendedServer = lazy(
    () =>
        import(
            '@/features/servers/components/client/Status/SuspendedServer.tsx'
        )
)
const DeferredOSSelection = lazy(
    () =>
        import(
            '@/features/servers/components/client/Status/DeferredOSSelection.tsx'
        )
)

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
    const { data: server } = useServer(serverUuid)
    useTitle(server?.name)

    const nav: SidebarNav = {
        key: `server:${serverUuid}`,
        back: { label: 'Servers', to: '/' },
        context: {
            title: server?.name ?? 'Server',
            icon: IconServer,
        },
        groups: [
            {
                items: [
                    {
                        icon: IconLayoutGrid,
                        label: 'Overview',
                        path: `/servers/${serverUuid}`,
                        activeOptions: { exact: true },
                    },
                    {
                        icon: IconChartBar,
                        label: 'Graphs',
                        path: `/servers/${serverUuid}/graphs`,
                    },
                ],
            },
            {
                label: 'Storage & Network',
                items: [
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
                ],
            },
            {
                label: 'Configuration',
                items: [
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
                ],
            },
        ],
    }

    const isInstalling =
        server?.status === 'installing' ||
        server?.status === 'install_failed' ||
        server?.status === 'restoring_backup' ||
        server?.status === 'deleting'
    const isDeferred = server?.status === 'deferred_os_selection'
    const isSuspended = server?.status === 'suspended'

    return (
        <AppLayout routes={nav}>
            <Suspense
                fallback={
                    <div className={'flex justify-center py-16'}>
                        <Spinner className={'size-6'} />
                    </div>
                }
            >
                {isDeferred ? (
                    <DeferredOSSelection server={server} />
                ) : isInstalling ? (
                    <InstallingServer server={server} />
                ) : isSuspended ? (
                    <SuspendedServer />
                ) : (
                    <Outlet />
                )}
            </Suspense>
        </AppLayout>
    )
}
