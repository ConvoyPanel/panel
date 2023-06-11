import getUser from '@/api/admin/users/getUser'
import useUserSWR, { getKey } from '@/api/admin/users/useUserSWR'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import { lazyLoad, query } from '@/routers/helpers'
import { lazy, useContext, useEffect } from 'react'
import { Outlet, RouteObject } from 'react-router-dom'

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
                element: lazyLoad(lazy(() => import('./AdminUserRouter'))),
                loader: ({ params }) => query(getKey(parseInt(params.id!)), () => getUser(parseInt(params.id!))),
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

const visibleRoutes = [
    {
        name: 'Settings',
        path: '/admin/users/:id/settings',
    },
    {
        name: 'Servers',
        path: '/admin/users/:id/servers',
    },
]

const AdminUserRouter = () => {
    const { setRoutes, setBreadcrumb } = useContext(NavigationBarContext)
    const { data: user } = useUserSWR()

    useEffect(() => {
        setRoutes(visibleRoutes)

        return () => setBreadcrumb(null)
    }, [])

    useEffect(() => {
        setBreadcrumb(user.name)
    }, [user.name])

    return <Outlet />
}

export default AdminUserRouter
