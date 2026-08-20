import useTitle from '@/hooks/use-title.ts'
import { processAxiosError } from '@/utils/http.ts'
import {
    IconChartBar,
    IconCopy,
    IconDatabase,
    IconDisc,
    IconHistory,
    IconLayoutGrid,
    IconLock,
    IconNetwork,
    IconRefresh,
    IconServer,
    IconShieldCog,
    IconShieldHalf,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'
import { Suspense, lazy } from 'react'

import { useUser } from '@/features/auth/api.ts'
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
    const { data: user } = useUser()
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
                    {
                        icon: IconHistory,
                        label: 'Activity',
                        path: `/servers/${serverUuid}/activity`,
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
                    {
                        icon: IconShieldHalf,
                        label: 'Firewall',
                        path: `/servers/${serverUuid}/firewall`,
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
            // The admin side is keyed by the numeric id, not the uuid this route
            // carries, so the jump has to wait for the server to load. Gated on
            // root_admin because a customer has nothing to land on over there.
            ...(user?.rootAdmin && server
                ? [
                      {
                          label: 'Admin',
                          items: [
                              {
                                  icon: IconShieldCog,
                                  label: 'Manage in Admin',
                                  path: `/admin/servers/${server.id}`,
                              },
                          ],
                      },
                  ]
                : []),
        ],
    }

    const isInstalling =
        server?.lifecycle === 'installing' ||
        server?.lifecycle === 'install_failed' ||
        server?.lifecycle === 'restoring_backup' ||
        server?.lifecycle === 'deleting'
    const isDeferred = server?.lifecycle === 'deferred_os_selection'
    // Its own axis, read from its own field -- a suspended server also has a
    // lifecycle, and the two can be true at once.
    const isSuspended = server?.suspendedAt != null

    return (
        <AppLayout routes={nav}>
            <Suspense
                fallback={
                    <div className={'flex justify-center py-16'}>
                        <Spinner className={'size-6'} />
                    </div>
                }
            >
                {/* Suspension wins over the lifecycle screens, matching the
                    API: a suspended server refuses every request regardless of
                    the stage it is in, so showing install progress it cannot
                    act on would just be a dead end. */}
                {isSuspended ? (
                    <SuspendedServer />
                ) : isDeferred ? (
                    <DeferredOSSelection server={server} />
                ) : isInstalling ? (
                    <InstallingServer server={server} />
                ) : (
                    <Outlet />
                )}
            </Suspense>
        </AppLayout>
    )
}
