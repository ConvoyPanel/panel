import { httpErrorToHuman } from '@/api/http'
import NavigationBar, { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, { NotFound, ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import routes from '@/routers/router'
import { ServerContext } from '@/state/server'
import { useContext, useEffect, useMemo, useState } from 'react'
import { Outlet, Route, Routes, useMatch } from 'react-router-dom'
import { ArrowPathIcon, ExclamationCircleIcon, NoSymbolIcon, XMarkIcon } from '@heroicons/react/24/outline'
import { AdminServerContext } from '@/state/admin/server'
import { AdminBanner } from '@/routers/AdminDashboardRouter'
import FixServerStatusButton from '@/components/admin/servers/FixServerStatusButton'

const AdminServerRouter = () => {
    const match = useMatch('/admin/servers/:id/*')
    const id = match!.params.id
    const [error, setError] = useState<string>()
    const server = AdminServerContext.useStoreState(state => state.server.data)
    const getServer = AdminServerContext.useStoreActions(actions => actions.server.getServer)
    const clearServerState = AdminServerContext.useStoreActions(actions => actions.clearServerState)

    const visibleRoutes = useMemo(
        () => [
            {
                name: 'Overview',
                path: `/admin/servers/${id}`,
                end: true,
            },
            {
                name: 'Settings',
                path: `/admin/servers/${id}/settings`,
            },
        ],
        [match?.params.id]
    )

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
