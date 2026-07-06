import { PaginatedNodes } from '@/types/node.ts'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getNodes, { NodeQueryParams } from '@/api/admin/nodes/getNodes.ts'

export const getKey = (params: NodeQueryParams) => ['nodes', params]

const useNodes = (params: NodeQueryParams) => {
    return useQuery<PaginatedNodes>({
        queryKey: getKey(params),
        queryFn: () => getNodes(params),
        placeholderData: keepPreviousData,
    })
}

export default useNodes
