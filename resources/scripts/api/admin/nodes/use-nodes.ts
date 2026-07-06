import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getNode from '@/api/admin/nodes/getNode.ts'
import getNodes, { NodeQueryParams } from '@/api/admin/nodes/getNodes.ts'

export const nodeQueries = {
    all: () => ['admin', 'nodes'] as const,
    lists: () => [...nodeQueries.all(), 'list'] as const,
    list: (params: NodeQueryParams) =>
        queryOptions({
            queryKey: [...nodeQueries.lists(), params] as const,
            queryFn: () => getNodes(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...nodeQueries.all(), 'detail'] as const,
    detail: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...nodeQueries.details(), id] as const,
            queryFn: () => getNode(id as number),
            enabled: typeof id === 'number',
        }),
}

const useNodes = (params: NodeQueryParams) => useQuery(nodeQueries.list(params))

export default useNodes
