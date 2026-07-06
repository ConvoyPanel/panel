import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import { nodeQueries } from '@/api/admin/nodes/use-nodes.ts'

export const preloadNode = (id: number) =>
    queryClient.prefetchQuery(nodeQueries.detail(id))

const useNode = (id?: number | null) => {
    const params = useParams({ strict: false }) as { nodeId?: number }
    const nodeId = id ?? params.nodeId

    return useQuery(nodeQueries.detail(nodeId))
}

export default useNode
