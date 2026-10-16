import { lazyLoad } from '@/routers/helpers'
import { Route } from '@/routers/router'
import { ServerContext } from '@/state/server'
import {
    ArrowPathIcon,
    ExclamationCircleIcon,
    NoSymbolIcon,
} from '@heroicons/react/24/outline'
import { lazy, useContext, useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Outlet, useMatches } from 'react-router-dom'

import { httpErrorToHuman } from '@/api/http'
import { EloquentStatus } from '@/api/server/types'

import ScreenBlock, { ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'

import InstallFailedScreen from '@/components/servers/InstallFailedScreen'


export const routes: Route[] = [
    {
        element: (
            <ServerContext.Provider>
                {lazyLoad(lazy(() => import('./ServerRouter')))}
            </ServerContext.Provider>
        ),
        path: '/servers/:id',
        children: [
            {
                index: true,
                element: lazyLoad(
                    lazy(
                        () =>
                            import(
                                '@/components/servers/overview/ServerOverviewContainer'
                            )
                    )
                ),
            },
            {
                path: 'backups',
                element: lazyLoad(
                    lazy(
                        () =>
                            import(
                                '@/components/servers/backups/BackupsContainer'
                            )
                    )
                ),
            },
            {
                path: 'settings',
                element: lazyLoad(
                    lazy(
                        () =>
                            import(
                                '@/components/servers/settings/ServerSettingsContainer'
                            )
                    )
                ),
                children: [
                    {
                        path: 'general',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/servers/settings/GeneralContainer'
                                    )
                            )
                        ),
                    },

                    {
                        path: 'hardware',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/servers/settings/HardwareContainer'
                                    )
                            )
                        ),
                    },

                    {
                        path: 'network',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/servers/settings/NetworkContainer'
                                    )
                            )
                        ),
                    },
                    {
                        path: 'security',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/servers/settings/SecurityContainer'
                                    )
                            )
                        ),
                    },
                ],
            },
            {
                path: 'terminal',
                element: lazyLoad(
                    lazy(
                        () =>
                            import(
                                '@/components/servers/terminal/ServerTerminalContainer'
                            )
                    )
                ),
            },
        ],
    },
]

const ServerRouter = () => {
    const { t: tStrings } = useTranslation('strings')
    const matches = useMatches()
    const id = matches[0].params.id
    const [error, setError] = useState<string>()
    const server = ServerContext.useStoreState(state => state.server.data)
    const getServer = ServerContext.useStoreActions(
        actions => actions.server.getServer
    )
    const clearServerState = ServerContext.useStoreActions(
        actions => actions.clearServerState
    )

    const visibleRoutes = useMemo(
        () => [
            {
                name: tStrings('overview'),
                path: `/servers/${id}`,
                end: true,
            },
            {
                name: tStrings('backup_other'),
                path: `/servers/${id}/backups`,
            },
            {
                name: tStrings('setting_other'),
                path: `/servers/${id}/settings`,
            },
        ],
        [id]
    )

    useEffect(() => {
        setError(undefined)

        getServer(id as string).catch((error: any) => {
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearServerState()
        }
    }, [id])

    // These statuses are only ever cleared by a queued job in the background, so
    // poll while the server is in one and let the screen move on by itself
    // instead of waiting for the user to refresh.
    const isBusy =
        server?.status === 'installing' ||
        server?.status === 'restoring_backup' ||
        server?.status === 'restoring_snapshot'

    useEffect(() => {
        if (!isBusy) return

        const interval = setInterval(() => {
            // A failed poll is retried on the next tick; the screen keeps
            // showing the last known status meanwhile.
            getServer(id as string).catch(() => {})
        }, 5000)

        return () => clearInterval(interval)
    }, [id, isBusy])

    const getScreenBlock = (status: EloquentStatus) => {
        switch (status) {
            case 'suspended':
                return (
                    <ScreenBlock
                        center
                        icon={NoSymbolIcon}
                        message='This server is suspended. Contact your provider or system administrator for help.'
                        title='Suspended'
                    />
                )
            case 'installing':
                return (
                    <ScreenBlock
                        center
                        icon={ArrowPathIcon}
                        spin
                        message='Your server is being installed. This can take from 1-15 minutes.'
                        title='Installing'
                    />
                )
            case 'restoring_backup':
                return (
                    <ScreenBlock
                        center
                        icon={ArrowPathIcon}
                        spin
                        message='Your server is being restored from a backup. This can take from 1-15 minutes.'
                        title='Restoring Backup'
                    />
                )
            case 'restoring_snapshot':
                return (
                    <ScreenBlock
                        center
                        icon={ArrowPathIcon}
                        spin
                        message='Your server is being restored from a snapshot. This can take from 1-15 minutes.'
                        title='Restoring Snapshot'
                    />
                )
            case 'install_failed':
                return <InstallFailedScreen />
            case null:
                return null
            default:
                return (
                    <ScreenBlock
                        center
                        icon={ExclamationCircleIcon}
                        message='Your server is in an unusable state. Please contact your administrator.'
                        title='Unusable'
                    />
                )
        }
    }

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
                    {getScreenBlock(server.status)}

                    {typeof server.status !== 'string' ? <Outlet /> : null}
                </>
            )}
        </>
    )
}

export default ServerRouter