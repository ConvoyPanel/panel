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
import { AdminBanner } from '@/routers/admin/DashboardRouter'
import FixServerStatusButton from '@/components/admin/servers/FixServerStatusButton'
import { AdminUserContext } from '@/state/admin/user'

const UserRouter = () => {
    const match = useMatch('/admin/users/:id/*')
    const id = match!.params.id
    const [error, setError] = useState<string>()
    const user = AdminUserContext.useStoreState(state => state.user.data)
    const getUser = AdminUserContext.useStoreActions(actions => actions.user.getUser)
    const clearUserState = AdminUserContext.useStoreActions(actions => actions.clearUserState)

    const visibleRoutes = useMemo(
        () => [
            {
                name: 'Settings',
                path: `/admin/users/${id}/settings`,
            },
            {
                name: 'Servers',
                path: `/admin/users/${id}/servers`,
            },
        ],
        [match?.params.id]
    )

    useEffect(() => {
        setError(undefined)

        getUser(parseInt(match!.params.id as string)).catch((error: any) => {
            console.error(error)
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearUserState()
        }
    }, [match?.params.id])

    return (
        <>
            <AdminBanner />
            <NavigationBar routes={visibleRoutes} breadcrumb={user?.name} />
            {!user ? (
                error ? (
                    <ErrorMessage message={error} />
                ) : (
                    <Spinner />
                )
            ) : (
                <Routes>
                    {routes.admin.user.map(route => (
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

export default UserRouter
