import { httpErrorToHuman } from '@/api/http'
import NavigationBar, { NavigationBarContext } from '@/components/elements/navigation/NavigationBar'
import { ErrorMessage, NotFound } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import { AdminBanner } from '@/routers/AdminDashboardRouter'
import { NodeContext } from '@/state/admin/node'
import { lazy, useContext, useEffect, useMemo, useState } from 'react'
import { Outlet, Route, RouteObject, Routes, useMatch } from 'react-router-dom'
import { useTranslation } from 'react-i18next'
import { lazyLoad } from '@/routers/helpers'

export const routes: RouteObject[] = [
    {
        path: 'nodes',
        children: [
            {
                index: true,
                element: lazyLoad(lazy(() => import('@/components/admin/nodes/NodesContainer'))),
            },
            {
                path: ':id',
                element: (
                    <NodeContext.Provider>
                        {lazyLoad(lazy(() => import('@/routers/AdminNodeRouter')))}
                    </NodeContext.Provider>
                ),
                children: [
                    {
                        index: true,
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/nodes/overview/NodeOverviewContainer'))
                        ),
                    },
                    {
                        path: 'servers',
                        element: lazyLoad(lazy(() => import('@/components/admin/nodes/servers/NodeServersContainer'))),
                    },
                    {
                        path: 'isos',
                        element: lazyLoad(lazy(() => import('@/components/admin/nodes/isos/IsosContainer'))),
                    },
                    {
                        path: 'templates',
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/nodes/templates/NodeTemplatesContainer'))
                        ),
                    },
                    {
                        path: 'addresses',
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/nodes/addresses/NodeAddressesContainer'))
                        ),
                    },
                    {
                        path: 'settings',
                        element: lazyLoad(
                            lazy(() => import('@/components/admin/nodes/settings/NodeSettingsContainer'))
                        ),
                        children: [
                            {
                                path: 'general',
                                element: lazyLoad(
                                    lazy(() => import('@/components/admin/nodes/settings/GeneralContainer'))
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
    const clearNodeState = NodeContext.useStoreActions(actions => actions.clearNodeState)

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

    return <>{!node ? error ? <ErrorMessage message={error} /> : <Spinner /> : <Outlet />}</>
}

export default AdminNodeRouter
