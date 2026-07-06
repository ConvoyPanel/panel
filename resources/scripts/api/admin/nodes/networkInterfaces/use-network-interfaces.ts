import { useParams } from '@tanstack/react-router'
import { queryOptions, useQuery } from '@tanstack/react-query'

import getNetworkInterfaces from '@/api/admin/nodes/networkInterfaces/getNetworkInterfaces.ts'

export const networkInterfaceQueries = {
    all: (nodeId: number) =>
        ['admin', 'nodes', nodeId, 'network-interfaces'] as const,
    list: (nodeId: number) =>
        queryOptions({
            queryKey: networkInterfaceQueries.all(nodeId),
            queryFn: () => getNetworkInterfaces(nodeId),
            enabled: !!nodeId,
        }),
}

const useNetworkInterfaces = (id?: number | null) => {
    const { nodeId: routeNodeId } = useParams({ strict: false })
    const nodeId = id ?? Number(routeNodeId)

    return useQuery(networkInterfaceQueries.list(nodeId))
}

export default useNetworkInterfaces
