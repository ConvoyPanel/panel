import { httpErrorToHuman } from '@/api/http'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, { ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import { lazy, useContext, useEffect, useState } from 'react'
import { Outlet, RouteObject, useMatch } from 'react-router-dom'
import { ExclamationCircleIcon } from '@heroicons/react/24/outline'
import { AdminServerContext } from '@/state/admin/server'
import FixServerStatusButton from '@/components/admin/servers/FixServerStatusButton'
import { useTranslation } from 'react-i18next'
import { ServerContext } from '@/state/server'
import { lazyLoad } from '@/routers/router'

export const routes: RouteObject[] = [
    {
        path: 'servers',
        children: [
            {
                index: true,
                element: lazyLoad(lazy(() => import('@/components/admin/servers/ServersContainer'))),
            },
            {
                path: ':id',
                element: (
                    <AdminServerContext.Provider>
                        {lazyLoad(lazy(() => import('@/routers/AdminServerRouter')))}
                    </AdminServerContext.Provider>
                ),
                children: [
                    {
                        index: true,
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/servers/overview/ServerOverviewContainer'))
                        ),
                    },
                    {
                        path: 'settings',
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/servers/settings/ServerSettingsContainer'))
                        ),
                        children: [
                            {
                                path: 'general',
                                element: lazyLoad(
                                    lazy(() => import('@/components/admin/servers/settings/GeneralContainer'))
                                ),
                            },
                            {
                                path: 'hardware',
                                element: lazyLoad(
                                    lazy(() => import('@/components/admin/servers/settings/ServerHardwareContainer'))
                                ),
                            },
                        ],
                    },
                ],
            },
        ],
    },
]

const AdminServerRouter = () => {
    const match = useMatch('/admin/servers/:id/*')
    const [error, setError] = useState<string>()
    const server = AdminServerContext.useStoreState(state => state.server.data)
    const getServer = AdminServerContext.useStoreActions(actions => actions.server.getServer)
    const clearServerState = AdminServerContext.useStoreActions(actions => actions.clearServerState)
    const { t: tStrings } = useTranslation('strings')

    const visibleRoutes = [
        {
            name: tStrings('overview'),
            path: `/admin/servers/:id`,
            end: true,
        },
        {
            name: tStrings('setting_other'),
            path: `/admin/servers/:id/settings`,
        },
    ]

    useEffect(() => {
        setError(undefined)

        getServer(match!.params.id as string).catch((error: any) => {
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearServerState()
        }
    }, [match?.params.id])

    const { setRoutes, setBreadcrumb } = useContext(NavigationBarContext)

    useEffect(() => {
        setRoutes(visibleRoutes)

        return () => setBreadcrumb(null)
    }, [])

    useEffect(() => {
        setBreadcrumb(server?.name)
    }, [server])

    return (
        <>
            {!server ? (
                error ? (
                    <ErrorMessage message={error} />
                ) : (
                    <Spinner />
                )
            ) : (
                <>
                    {server.status === 'deleting' || server.status === 'deletion_failed' ? (
                        <ScreenBlock
                            center
                            icon={ExclamationCircleIcon}
                            message={
                                server.status === 'deleting'
                                    ? "This server is being deleted. If you think this is an error, click below to make the server accessible. Clicking the button will not stop the server from being deleted if it's in progress"
                                    : 'This server failed to delete. if you think this is an error, click below to make the server accessible. The server MAY not be in a usable state.'
                            }
                            title={server.status === 'deleting' ? 'Deleting' : 'Failed to Delete'}
                        >
                            <FixServerStatusButton />
                        </ScreenBlock>
                    ) : (
                        <Outlet />
                    )}
                </>
            )}
        </>
    )
}

export default AdminServerRouter
