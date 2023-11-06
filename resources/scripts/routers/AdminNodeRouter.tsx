import { lazyLoad, query } from '@/routers/helpers'
import { Route } from '@/routers/router'
import { lazy, useContext, useEffect } from 'react'
import { useTranslation } from 'react-i18next'
import { Outlet } from 'react-router-dom'

import getNode from '@/api/admin/nodes/getNode'
import useNodeSWR, { getKey as getPoolKey } from '@/api/admin/nodes/useNodeSWR'

import Spinner from '@/components/elements/Spinner'
import { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'


export const routes: Route[] = [
    {
        path: 'nodes',
        children: [
            {
                index: true,
                element: lazyLoad(
                    lazy(
                        () => import('@/components/admin/nodes/NodesContainer')
                    )
                ),
            },
            {
                path: ':nodeId',
                loader: ({ params }) =>
                    query(getPoolKey(parseInt(params.nodeId!)), () =>
                        getNode(parseInt(params.nodeId!))
                    ),
                element: lazyLoad(
                    lazy(() => import('@/routers/AdminNodeRouter'))
                ),
                children: [
                    {
                        index: true,
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/nodes/overview/NodeOverviewContainer'
                                    )
                            )
                        ),
                    },
                    {
                        path: 'servers',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/nodes/servers/NodeServersContainer'
                                    )
                            )
                        ),
                    },
                    {
                        path: 'isos',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/nodes/isos/IsosContainer'
                                    )
                            )
                        ),
                    },
                    {
                        path: 'templates',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/nodes/templates/NodeTemplatesContainer'
                                    )
                            )
                        ),
                    },
                    {
                        path: 'addresses',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/nodes/addresses/NodeAddressesContainer'
                                    )
                            )
                        ),
                    },
                    {
                        path: 'settings',
                        element: lazyLoad(
                            lazy(
                                () =>
                                    import(
                                        '@/components/admin/nodes/settings/NodeSettingsContainer'
                                    )
                            )
                        ),
                        children: [
                            {
                                path: 'general',
                                element: lazyLoad(
                                    lazy(
                                        () =>
                                            import(
                                                '@/components/admin/nodes/settings/GeneralContainer'
                                            )
                                    )
                                ),
                            },
                        ],
                    },
                ],
            },
        ],
    },
]

const AdminNodeRouter = () => {
    const { t: tStrings } = useTranslation('strings')
    const { data: node } = useNodeSWR()

    const routes = [
        {
            name: tStrings('overview'),
            path: `/admin/nodes/:nodeId`,
            end: true,
        },
        {
            name: tStrings('server_other'),
            path: `/admin/nodes/:nodeId/servers`,
        },
        {
            name: tStrings('iso_other'),
            path: `/admin/nodes/:nodeId/isos`,
        },
        {
            name: tStrings('template_other'),
            path: `/admin/nodes/:nodeId/templates`,
        },
        {
            name: tStrings('address_other'),
            path: `/admin/nodes/:nodeId/addresses`,
        },
        {
            name: tStrings('setting_other'),
            path: `/admin/nodes/:nodeId/settings`,
        },
    ]

    const { setRoutes, setBreadcrumb } = useContext(NavigationBarContext)

    useEffect(() => {
        setRoutes(routes)

        return () => setBreadcrumb(null)
    }, [])

    useEffect(() => {
        setBreadcrumb(node.name)
    }, [node])

    return <>{!node ? <Spinner /> : <Outlet />}</>
}

export default AdminNodeRouter