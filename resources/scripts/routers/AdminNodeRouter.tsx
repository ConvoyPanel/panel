import { lazyLoad, query } from '@/routers/helpers'
import { Route } from '@/routers/router'
import { NodeContext } from '@/state/admin/node'
import { lazy, useContext, useEffect, useMemo, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Outlet, useMatch } from 'react-router-dom'

import getAddressPool from '@/api/admin/addressPools/getAddressPool'
import { getKey as getPoolKey } from '@/api/admin/addressPools/useAddressPoolSWR'
import { httpErrorToHuman } from '@/api/http'

import { ErrorMessage } from '@/components/elements/ScreenBlock'
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
                    query(getPoolKey(parseInt(params.id!)), () =>
                        getAddressPool(parseInt(params.id!))
                    ),
                element: (
                    <NodeContext.Provider>
                        {lazyLoad(
                            lazy(() => import('@/routers/AdminNodeRouter'))
                        )}
                    </NodeContext.Provider>
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
    const match = useMatch('/admin/nodes/:id/*')
    const [error, setError] = useState<string>()
    const id = match!.params.id
    const node = NodeContext.useStoreState(state => state.node.data)
    const getNode = NodeContext.useStoreActions(actions => actions.node.getNode)
    const clearNodeState = NodeContext.useStoreActions(
        actions => actions.clearNodeState
    )

    const visibleRoutes = useMemo(
        () => [
            {
                name: tStrings('overview'),
                path: `/admin/nodes/${id}`,
                end: true,
            },
            {
                name: tStrings('server_other'),
                path: `/admin/nodes/${id}/servers`,
            },
            {
                name: tStrings('iso_other'),
                path: `/admin/nodes/${id}/isos`,
            },
            {
                name: tStrings('template_other'),
                path: `/admin/nodes/${id}/templates`,
            },
            {
                name: tStrings('address_other'),
                path: `/admin/nodes/${id}/addresses`,
            },
            {
                name: tStrings('setting_other'),
                path: `/admin/nodes/${id}/settings`,
            },
        ],
        [match?.params.id]
    )

    useEffect(() => {
        setError(undefined)

        getNode(parseInt(match!.params.id as string)).catch((error: any) => {
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearNodeState()
        }
    }, [match?.params.id])

    const { setRoutes, setBreadcrumb } = useContext(NavigationBarContext)

    useEffect(() => {
        setRoutes(visibleRoutes)

        return () => setBreadcrumb(null)
    }, [])

    useEffect(() => {
        setBreadcrumb(node?.name)
    }, [node])

    return (
        <>
            {!node ? (
                error ? (
                    <ErrorMessage message={error} />
                ) : (
                    <Spinner />
                )
            ) : (
                <Outlet />
            )}
        </>
    )
}

export default AdminNodeRouter