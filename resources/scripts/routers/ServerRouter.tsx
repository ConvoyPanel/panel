import { httpErrorToHuman } from '@/api/http'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, { NotFound, ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import routes from '@/routers/routes'
import { ServerContext } from '@/state/server'
import { ReactNode, useEffect, useMemo, useState } from 'react'
import { Outlet, Route, Routes, useMatch, useMatches } from 'react-router-dom'
import { ArrowPathIcon, ExclamationCircleIcon, NoSymbolIcon, XMarkIcon } from '@heroicons/react/24/outline'
import { EloquentStatus } from '@/api/server/types'

const ServerRouter = () => {
    const match = useMatch('/servers/:id/*')
    const id = match!.params.id
    const [error, setError] = useState<string>()
    const server = ServerContext.useStoreState(state => state.server.data)
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer)
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState)
    const matches = useMatches()
    console.log({ matches })

    const visibleRoutes = useMemo(
        () => [
            {
                name: 'Overview',
                path: `/servers/${id}`,
            },
            {
                name: 'Backups',
                path: `/servers/${id}/backups`,
            },
            {
                name: 'Settings',
                path: `/servers/${id}/settings`,
            },
        ],
        [match?.params.id]
    )

    useEffect(() => {
        setError(undefined)

        getServer(match!.params.id as string).catch((error: any) => {
            console.error(error)
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearServerState()
        }
    }, [match?.params.id])

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
                        message='Your server is being installed. This can take from 1-15 minutes.'
                        title='Installing'
                    />
                )
            case 'restoring_backup':
                return (
                    <ScreenBlock
                        center
                        icon={ArrowPathIcon}
                        message='Your server is being restored from a backup. This can take from 1-15 minutes.'
                        title='Restoring Backup'
                    />
                )
            case 'restoring_snapshot':
                return (
                    <ScreenBlock
                        center
                        icon={ArrowPathIcon}
                        message='Your server is being restored from a snapshot. This can take from 1-15 minutes.'
                        title='Restoring Snapshot'
                    />
                )
            case 'install_failed':
                return (
                    <ScreenBlock
                        center
                        icon={ExclamationCircleIcon}
                        message='Your server failed to install. Please contact your administrator.'
                        title='Install failed'
                    />
                )
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

    return (
        <>
            <NavigationBar routes={visibleRoutes} breadcrumb={server?.name} />
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
