import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import getNetworkInterfaces from '@/api/admin/nodes/networkInterfaces/getNetworkInterfaces.ts'

export const getKey = (nodeId: number) => ['node.network-interfaces', nodeId]

const useNetworkInterfaces = (id?: number | null) => {
    const { nodeId: routeNodeId } = useParams({ strict: false })
    const nodeId = id ?? Number(routeNodeId)

    return useQuery({
        queryKey: getKey(nodeId),
        queryFn: () => getNetworkInterfaces(nodeId),
        enabled: !!nodeId,
    })
}

export default useNetworkInterfaces
