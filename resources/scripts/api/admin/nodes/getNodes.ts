import { PaginatedNodes } from '@/types/node.ts'
import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

import { rawDataToNode } from '@/api/transformers/node.ts'

export type NodeQueryParams = QueryBuilderParams<
    '*' | 'id' | 'display_name' | 'fqdn' | 'location_id' | 'coterm_id'
>

const getNodes = async (
    params: NodeQueryParams
): Promise<PaginatedNodes> => {
    const { data } = await axios.get('/api/admin/nodes', {
        params: withQueryBuilderParams(params),
    })

    return {
        items: data.data.map(rawDataToNode),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getNodes
