import type { PaginatedNetworkInterfaces, NetworkInterface } from '@/types/network-interface.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

export type AttachedNodesQueryParams = QueryBuilderParams<'*' | 'id'>

const getAttachedNodes = async (
    addressBlockGroupId: number,
    params: AttachedNodesQueryParams
): Promise<PaginatedNetworkInterfaces> => {
    const { data } = await axios.get<PaginatedResponse<NetworkInterface>>(
        `/api/admin/address-block-groups/${addressBlockGroupId}/nodes`,
        { params: withQueryBuilderParams(params) }
    )

    return {
        items: data.items,
        pagination: data.pagination,
    }
}

export default getAttachedNodes
