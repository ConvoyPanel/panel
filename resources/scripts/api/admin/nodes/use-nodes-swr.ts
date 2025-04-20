import { PaginatedNodes } from '@/types/node.ts'
import useSWR from 'swr'

import getNodes, { NodeQueryParams } from '@/api/admin/nodes/getNodes.ts'

export const getKey = (params: NodeQueryParams) => ['nodes', params]

const useNodesSWR = (params: NodeQueryParams) => {
    return useSWR<PaginatedNodes>(getKey(params), () => getNodes(params))
}

export default useNodesSWR
