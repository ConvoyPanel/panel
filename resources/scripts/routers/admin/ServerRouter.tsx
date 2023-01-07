import { httpErrorToHuman } from '@/api/http'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import ScreenBlock, { NotFound, ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import routes from '@/routers/routes'
import { ServerContext } from '@/state/server'
import { useEffect, useMemo, useState } from 'react'
import { Route, Routes, useMatch } from 'react-router-dom'
import { ArrowPathIcon, ExclamationCircleIcon, NoSymbolIcon, XMarkIcon } from '@heroicons/react/24/outline'
import { AdminServerContext } from '@/state/admin/server'

const ServerRouter = () => {
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
            console.error(error)
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearServerState()
        }
    }, [match?.params.id])

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
                <Routes>
                    {routes.admin.server.map(route => (
                        <Route
                            key={route.path}
                            path={route.path}
                            element={
                                <Spinner.Suspense screen={false}>
                                    <route.component />
                                </Spinner.Suspense>
                            }
                        />
                    ))}

                    <Route path={'*'} element={<NotFound full />} />
                </Routes>
            )}
        </>
    )
}

export default ServerRouter
