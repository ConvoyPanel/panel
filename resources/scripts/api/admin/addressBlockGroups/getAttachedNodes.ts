import { PaginatedNodes } from '@/types/node.ts'
import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

import { rawDataToNode } from '@/api/transformers/node.ts'

export type AttachedNodesQueryParams = QueryBuilderParams<'*' | 'id'>

const getAttachedNodes = async (
    addressBlockGroupId: number,
    params: AttachedNodesQueryParams
): Promise<PaginatedNodes> => {
    const { data } = await axios.get(
        `/api/admin/address-block-groups/${addressBlockGroupId}/nodes`,
        {
            params: withQueryBuilderParams(params),
        }
    )

    return {
        items: data.data.map(rawDataToNode),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAttachedNodes
