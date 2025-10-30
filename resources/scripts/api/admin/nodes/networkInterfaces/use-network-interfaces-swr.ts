import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'
import getNetworkInterfaces from '@/api/admin/nodes/networkInterfaces/getNetworkInterfaces.ts'

export const getKey = (nodeId: number) => ['node.network-interfaces', nodeId]

const useNetworkInterfacesSWR = (id?: number | null) => {
    const { nodeId: routeNodeId } = useParams({ strict: false }) as { nodeId?: number }
    const nodeId = (id ?? routeNodeId)

    return useSWR(
        typeof nodeId === 'number' ? getKey(nodeId) : null,
        () => getNetworkInterfaces(nodeId as number)
    )
}

export default useNetworkInterfacesSWR