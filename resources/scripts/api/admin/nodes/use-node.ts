import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getNode from '@/api/admin/nodes/getNode.ts'

export const getKey = (id: number | null | undefined) => ['node', id]

export const preloadNode = (id: number) =>
    queryClient.prefetchQuery({ queryKey: getKey(id), queryFn: () => getNode(id) })

const useNode = (id?: number | null) => {
    const params = useParams({ strict: false }) as { nodeId?: number }
    const nodeId = id ?? params.nodeId

    return useQuery({
        queryKey: getKey(nodeId),
        queryFn: () => getNode(nodeId as number),
        enabled: typeof nodeId === 'number',
    })
}

export default useNode
