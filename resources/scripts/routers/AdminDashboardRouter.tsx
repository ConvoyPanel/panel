import { routes as adminCotermRoutes } from '@/routers/AdminCotermRouter'
import { routes as adminIpamRoutes } from '@/routers/AdminIpamRouter'
import { routes as adminNodeRoutes } from '@/routers/AdminNodeRouter'
import { routes as adminServerRoutes } from '@/routers/AdminServerRouter'
import { routes as adminUserRoutes } from '@/routers/AdminUserRouter'
import { lazyLoad } from '@/routers/helpers'
import AuthenticatedRoutes from '@/routers/middleware/AuthenticatedRoutes'
import { Route } from '@/routers/router'
import { HomeIcon } from '@heroicons/react/20/solid'
import { lazy, useContext, useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, Outlet, useMatch } from 'react-router-dom'

import ContentContainer from '@/components/elements/ContentContainer'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'


export const routes: Route[] = [
    {
        path: '/admin',
        element: (
            <AuthenticatedRoutes requireRootAdmin>
                {lazyLoad(lazy(() => import('@/routers/AdminDashboardRouter')))}
            </AuthenticatedRoutes>
        ),
        handle: {
            crumb: () => ({
                to: '/admin',
                element: (
                    <HomeIcon
                        className={
                            'w-4 h-4 text-accent-500 hover:text-accent-800 transition'
                        }
                    />
                ),
            }),
        },
        children: [
            {
                index: true,
                element: lazyLoad(
                    lazy(
                        () =>
                            import(
                                '@/components/admin/overview/OverviewContainer'
                            )
                    )
                ),
            },
            {
                path: 'locations',
                element: lazyLoad(
                    lazy(
                        () =>
                            import(
                                '@/components/admin/locations/LocationsContainer'
                            )
                    )
                ),
            },
            ...adminNodeRoutes,
            ...adminServerRoutes,
            ...adminIpamRoutes,
            ...adminUserRoutes,
            ...adminCotermRoutes,
            {
                path: 'tokens',
                element: lazyLoad(
                    lazy(
                        () =>
                            import('@/components/admin/tokens/TokensContainer')
                    )
                ),
            },
        ],
    },
]

export const AdminBanner = () => (
    <div className='bg-foreground py-1'>
        <ContentContainer>
            <Link to='/'>
                <p className='text-background text-xs font-medium uppercase tracking-wide'>
                    Exit Administration
                </p>
            </Link>
        </ContentContainer>
    </div>
)

const AdminDashboardRouter = () => {
    const { setRoutes } = useContext(NavigationBarContext)
    const isDashboardArea = useMatch('/admin/:id/')
    const isDashboardArea2 = useMatch('/admin')
    const { t: tStrings } = useTranslation('strings')

    const navRoutes = [
        {
            name: tStrings('overview'),
            path: '/admin',
            end: true,
        },
        {
            name: tStrings('location_other'),
            path: '/admin/locations',
        },
        {
            name: tStrings('node_other'),
            path: '/admin/nodes',
        },
        {
            name: tStrings('server_other'),
            path: '/admin/servers',
        },
        {
            name: tStrings('ipam'),
            path: '/admin/ipam',
        },
        {
            name: tStrings('user_other'),
            path: '/admin/users',
        },
        {
            name: 'Coterms',
            path: '/admin/coterms',
        },
        {
            name: tStrings('token_other'),
            path: '/admin/tokens',
        },
    ]

    useEffect(() => {
        if (Boolean(isDashboardArea) || Boolean(isDashboardArea2)) {
            setRoutes(navRoutes)
        }
    }, [isDashboardArea, isDashboardArea2])

    return (
        <>
            <Outlet />
        </>
    )
}

export default AdminDashboardRouter