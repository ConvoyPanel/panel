import { httpErrorToHuman } from '@/api/http'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import { ErrorMessage } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import { lazy, useContext, useEffect, useMemo, useState } from 'react'
import { Outlet, RouteObject, useMatch } from 'react-router-dom'
import { AdminUserContext } from '@/state/admin/user'
import { lazyLoad } from '@/routers/helpers'

export const routes: RouteObject[] = [
    {
        path: 'users',
        children: [
            {
                index: true,
                element: lazyLoad(lazy(() => import('@/components/admin/users/UsersContainer'))),
            },
            {
                path: ':id',
                element: (
                    <AdminUserContext.Provider>
                        {lazyLoad(lazy(() => import('./AdminUserRouter')))}
                    </AdminUserContext.Provider>
                ),
                children: [
                    {
                        path: 'settings',
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/users/settings/UserSettingsContainer'))
                        ),
                        children: [
                            {
                                path: 'general',
                                element: lazyLoad(
                                    lazy(() => import('@/components/admin/users/settings/GeneralContainer'))
                                ),
                            },
                        ],
                    },
                    {
                        path: 'servers',
                        element: lazyLoad(lazy(() => import('@/components/admin/users/servers/UserServersContainer'))),
                    },
                ],
            },
        ],
    },
]

const AdminUserRouter = () => {
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
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearUserState()
        }
    }, [match?.params.id])

    const { setRoutes, setBreadcrumb } = useContext(NavigationBarContext)

    useEffect(() => {
        setRoutes(visibleRoutes)

        return () => setBreadcrumb(null)
    }, [])

    useEffect(() => {
        setBreadcrumb(user?.name)
    }, [user])

    return <>{!user ? error ? <ErrorMessage message={error} /> : <Spinner /> : <Outlet />}</>
}

export default AdminUserRouter
