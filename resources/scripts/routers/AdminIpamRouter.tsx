import { Navigate, Outlet, RouteObject } from 'react-router-dom'
import { lazyLoad, query } from '@/routers/helpers'
import { lazy, useContext, useEffect } from 'react'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import { getKey as getPoolKey } from '@/api/admin/addressPools/addresses/useAddressPoolSWR'
import getAddressPool from '@/api/admin/addressPools/getAddressPool'
import { getKey as getAddressesKey } from '@/api/admin/addressPools/useAddressesSWR'
import getAddresses from '@/api/admin/addressPools/getAddresses'
import { Translation, useTranslation } from 'react-i18next'
import { Route } from '@/routers/router'
import { HomeIcon } from '@heroicons/react/20/solid'

export const routes: Route[] = [
    {
        path: 'ipam',
        handle: {
            crumb: () => ({
                to: '/admin/ipam',
                element: <Translation ns={'strings'}>{t => t('ipam')}</Translation>,
            }),
        },
        children: [
            {
                index: true,
                element: lazyLoad(lazy(() => import('@/components/admin/ipam/IpamContainer'))),
            },
            {
                path: ':id',
                element: lazyLoad(lazy(() => import('./AdminIpamRouter'))),
                loader: ({ params }) =>
                    query(getPoolKey(parseInt(params.id!)), () => getAddressPool(parseInt(params.id!))),
                handle: {
                    crumb: data => ({
                        to: `/admin/ipam/${data.id}`,
                        element: data.name,
                    }),
                },
                children: [
                    {
                        index: true,
                        element: <Navigate to={'./addresses'} replace />,
                    },
                    {
                        path: 'addresses',
                        loader: ({ params }) => {
                            const id = parseInt(params.id!)
                            const page = params.page ? parseInt(params.page) : 1

                            return query(getAddressesKey(id, page, ''), () =>
                                getAddresses(id, { page, query: '', include: ['server'] })
                            )
                        },
                        element: lazyLoad(lazy(() => import('@/components/admin/ipam/addresses/AddressesContainer'))),
                    },
                ],
            },
        ],
    },
]

const AdminIpamRouter = () => {
    const { setRoutes, setBreadcrumb } = useContext(NavigationBarContext)
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
            name: tStrings('token_other'),
            path: '/admin/tokens',
        },
    ]

    useEffect(() => {
        setRoutes(navRoutes)
    }, [])

    return <Outlet />
}

export default AdminIpamRouter
