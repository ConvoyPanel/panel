import type { Node, PaginatedNodes } from '@/types/node.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

export type NodeQueryParams = QueryBuilderParams<
    '*' | 'id' | 'display_name' | 'fqdn' | 'location_id' | 'coterm_id'
>

const getNodes = async (params: NodeQueryParams): Promise<PaginatedNodes> => {
    const { data } = await axios.get<PaginatedResponse<Node>>('/api/admin/nodes', {
        params: withQueryBuilderParams(params),
    })

    return {
        items: data.items,
        pagination: data.pagination,
    }
}

export default getNodes
