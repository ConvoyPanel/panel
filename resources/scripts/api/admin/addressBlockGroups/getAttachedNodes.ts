import { rawDataToNetworkInterface } from '@/api/transformers/network-interface.ts'
import { PaginatedNetworkInterfaces } from '@/types/network-interface.ts'
import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

export type AttachedNodesQueryParams = QueryBuilderParams<'*' | 'id'>

const getAttachedNodes = async (
    addressBlockGroupId: number,
    params: AttachedNodesQueryParams
): Promise<PaginatedNetworkInterfaces> => {
    const { data } = await axios.get(
        `/api/admin/address-block-groups/${addressBlockGroupId}/nodes`,
        {
            params: withQueryBuilderParams(params),
        }
    )

    return {
        items: data.data.map(rawDataToNetworkInterface),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAttachedNodes
