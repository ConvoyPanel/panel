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
    IconUsers,
} from '@tabler/icons-react'
import { Outlet, createFileRoute } from '@tanstack/react-router'
import { Suspense, lazy } from 'react'

import { useUser } from '@/features/auth/api.ts'
import { useServer, preloadServer } from '@/features/servers/detail/api.ts'

import AppLayout from '@/components/layouts/AppLayout.tsx'
import Spinner from '@/components/ui/Spinner.tsx'

import {
    type Route as NavRoute,
    SidebarNav,
} from '@/components/ui/Navigation/Navigation.types.ts'

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


const ServerLayout = () => {
    const { serverUuid } = Route.useParams()
    const { data: server } = useServer(serverUuid)
    const { data: user } = useUser()
    useTitle(server?.name)

    /*
     * A tab the account cannot open is left out rather than disabled. The owner and an operator
     * are handed the whole catalog by the API, so this only ever removes anything for a sub-user,
     * and the endpoints behind each tab refuse independently -- this is the nav agreeing with
     * them rather than the gate itself.
     *
     * While the payload is still loading there is nothing to filter against, so everything shows.
     */
    const can = (...permissions: App.Enums.Server.ServerPermission[]) =>
        server === undefined ||
        permissions.some(permission => server.permissions.includes(permission))

    const only = (allowed: boolean, ...items: NavRoute[]): NavRoute[] =>
        allowed ? items : []

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
                    ...only(can('statistics.read'), {
                        icon: IconChartBar,
                        label: 'Graphs',
                        path: `/servers/${serverUuid}/graphs`,
                    }),
                    ...only(can('activity.read'), {
                        icon: IconHistory,
                        label: 'Activity',
                        path: `/servers/${serverUuid}/activity`,
                    }),
                ],
            },
            {
                label: 'Storage & Network',
                items: [
                    ...only(can('backup.read'), {
                        icon: IconCopy,
                        label: 'Backups',
                        path: `/servers/${serverUuid}/backups`,
                    }),
                    ...only(can('settings.media'), {
                        icon: IconDisc,
                        label: 'ISO Library',
                        path: `/servers/${serverUuid}/iso-library`,
                    }),
                    ...only(can('settings.boot-order', 'settings.reinstall'), {
                        icon: IconDatabase,
                        label: 'Storage',
                        path: `/servers/${serverUuid}/storage`,
                    }),
                    ...only(can('settings.network'), {
                        icon: IconNetwork,
                        label: 'Networking',
                        path: `/servers/${serverUuid}/networking`,
                    }),
                    ...only(can('firewall.read'), {
                        icon: IconShieldHalf,
                        label: 'Firewall',
                        path: `/servers/${serverUuid}/firewall`,
                    }),
                ],
            },
            {
                label: 'Configuration',
                items: [
                    ...only(can('settings.auth'), {
                        icon: IconLock,
                        label: 'Security',
                        path: `/servers/${serverUuid}/security`,
                    }),
                    ...only(can('settings.reinstall'), {
                        icon: IconRefresh,
                        label: 'Rebuild',
                        path: `/servers/${serverUuid}/rebuild`,
                    }),
                    // Owner only, and not delegable: a permission to grant permissions would be
                    // a permission to grant every permission.
                    ...only(server?.isOwner !== false, {
                        icon: IconUsers,
                        label: 'Sharing',
                        path: `/servers/${serverUuid}/sharing`,
                    }),
                ],
            },
            // The admin side is keyed by the numeric id, not the uuid this route
            // carries, so the jump has to wait for the server to load. Gated on
            // the permission that opens that screen, because a customer has
            // nothing to land on over there.
            ...(user?.adminPermissions?.includes('servers.read') && server
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

export const Route = createFileRoute('/_app/servers/$serverUuid')({
    loader: ({ params: { serverUuid } }) =>
        preloadServer(serverUuid).catch(processAxiosError),
    component: ServerLayout,
    staticData: {
        title: 'Dashboard',
    },
})
