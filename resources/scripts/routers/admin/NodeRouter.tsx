import { httpErrorToHuman } from '@/api/http'
import NavigationBar from '@/components/elements/navigation/NavigationBar'
import { ErrorMessage, NotFound } from '@/components/elements/ScreenBlock'
import Spinner from '@/components/elements/Spinner'
import { AdminBanner } from '@/routers/admin/AdminDashboardRouter'
import { NodeContext } from '@/state/admin/node'
import { useEffect, useMemo, useState } from 'react'
import { Outlet, Route, Routes, useMatch } from 'react-router-dom'

const NodeRouter = () => {
    const match = useMatch('/admin/nodes/:id/*')
    const [error, setError] = useState<string>()
    const id = match!.params.id
    const node = NodeContext.useStoreState(state => state.node.data)
    const getNode = NodeContext.useStoreActions(actions => actions.node.getNode)
    const clearNodeState = NodeContext.useStoreActions(actions => actions.clearNodeState)

    const visibleRoutes = useMemo(
        () => [
            {
                name: 'Overview',
                path: `/admin/nodes/${id}`,
            },
            {
                name: 'Servers',
                path: `/admin/nodes/${id}/servers`,
            },
            {
                name: 'ISO Library',
                path: `/admin/nodes/${id}/isos`,
            },
            {
                name: 'Templates',
                path: `/admin/nodes/${id}/templates`,
            },
            {
                name: 'Addresses',
                path: `/admin/nodes/${id}/addresses`,
            },
            {
                name: 'Settings',
                path: `/admin/nodes/${id}/settings`,
            },
        ],
        [match?.params.id]
    )

    useEffect(() => {
        setError(undefined)

        getNode(parseInt(match!.params.id as string)).catch((error: any) => {
            console.error(error)
            setError(httpErrorToHuman(error))
        })

        return () => {
            clearNodeState()
        }
    }, [match?.params.id])

    return (
        <>
            <AdminBanner />
            <NavigationBar routes={visibleRoutes} breadcrumb={node?.name} />
            {!node ? error ? <ErrorMessage message={error} /> : <Spinner /> : <Outlet />}
        </>
    )
}

export default NodeRouter
