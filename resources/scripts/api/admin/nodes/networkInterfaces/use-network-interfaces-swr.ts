import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'
import getNetworkInterfaces from '@/api/admin/nodes/networkInterfaces/getNetworkInterfaces.ts'

export const getKey = (nodeId: number) => ['node.network-interfaces', nodeId]

const useNetworkInterfacesSWR = (id?: number | null) => {
    const { nodeId: routeNodeId } = useParams({ strict: false })
    const nodeId = id ?? Number(routeNodeId)

    return useSWR(
        nodeId ? getKey(nodeId) : null,
        () => getNetworkInterfaces(nodeId)
    )
}

export default useNetworkInterfacesSWR