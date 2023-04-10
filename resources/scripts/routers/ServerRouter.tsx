import { httpErrorToHuman } from '@/api/http'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, { ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import { ServerContext } from '@/state/server'
import { useContext, useEffect, useMemo, useState } from 'react'
import { Outlet, useMatches } from 'react-router-dom'
import { ArrowPathIcon, ExclamationCircleIcon, NoSymbolIcon } from '@heroicons/react/24/outline'
import { EloquentStatus } from '@/api/server/types'
import { useTranslation } from 'react-i18next'

const ServerRouter = () => {
    const { t: tStrings } = useTranslation('strings')
    const matches = useMatches()
    const id = matches[0].params.id
    const [error, setError] = useState<string>()
    const server = ServerContext.useStoreState(state => state.server.data)
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer)
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState)

    const visibleRoutes = useMemo(
        () => [
            {
                name: tStrings('overview'),
                path: `/servers/${id}`,
                end: true,
            },
            {
                name: tStrings('backup', { count: 2 }),
                path: `/servers/${id}/backups`,
            },
            {
                name: tStrings('setting', { count: 2 }),
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
