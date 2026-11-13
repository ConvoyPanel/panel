import { lazyLoad, query } from '@/routers/helpers'
import { Route } from '@/routers/router'
import { lazy, useContext, useEffect } from 'react'
import { Translation, useTranslation } from 'react-i18next'
import { Navigate, Outlet } from 'react-router-dom'

import getAddresses from '@/api/admin/addressPools/addresses/getAddresses'
import getAddressPool from '@/api/admin/addressPools/getAddressPool'
import { getKey as getPoolKey } from '@/api/admin/addressPools/useAddressPoolSWR'
import { getKey as getAddressesKey } from '@/api/admin/addressPools/useAddressesSWR'

import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'

export const routes: Route[] = [
    {
        path: 'ipam',
        handle: {
            crumb: () => ({
                to: '/admin/ipam',
                element: (
                    <Translation ns={'strings'}>{t => t('ipam')}</Translation>
                ),
            }),
        },
        children: [
            {
                index: true,
                element: lazyLoad(
                    lazy(() => import('@/components/admin/ipam/IpamContainer'))
                ),
            },
            {
                path: ':poolId',
                loader: async ({ params }) => {
                    try {
                        const poolId = parseInt(params.poolId!)
                        const pool = await query(getPoolKey(poolId), () =>
                            getAddressPool(poolId)
                        )
                        return pool
                    } catch (error) {
                        return null
                    }
                },
                element: lazyLoad(lazy(() => import('./AdminIpamRouter'))),
                handle: {
                    // `data` is the loader's pool, which has an `id`, not a
                    // `poolId`: the crumb linked to /admin/ipam/undefined.
                    crumb: data => ({
                        to: `/admin/ipam/${data?.id}`,
                        element: data?.name,
                    }),
                },
                children: [
                    {
                        index: true,
                        element: <Navigate to={'./addresses'} replace />,
                    },
                    {
                        path: 'addresses',
                        loader: async ({ params }) => {
                            try {
                                const id = parseInt(params.poolId!)
                                const page = params.page
                                    ? parseInt(params.page)
                                    : 1
                                const addresses = await query(
                                    getAddressesKey(id, page, ''),
                                    () =>
                                        getAddresses(id, {
                                            page,
                                            query: '',
                                            include: ['server'],
                                        })
                                )
                                return addresses
                            } catch (error) {
                                return null
                            }
                        },
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/ipam/addresses/AddressesContainer'
                                    )
                            )
                        ),
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
            name: 'Coterms',
            path: '/admin/coterms',
        },
        {
            name: tStrings('token_other'),
            path: '/admin/tokens',
        },
        {
            name: tStrings('setting_other'),
            path: '/admin/settings',
        },
    ]

    useEffect(() => {
        setRoutes(navRoutes)
    }, [])

    return <Outlet />
}

export default AdminIpamRouter
