import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'
import { PaginatedServers } from '@/types/server'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

export type CompatibleServerQueryParams = QueryBuilderParams<'*' | 'node_id' | 'user_id' | 'name'>

export type ServerInclude = 'node'

const getCompatibleServers = async (
    addressBlockGroupId: number,
    params: CompatibleServerQueryParams,
    include?: ServerInclude[]
): Promise<PaginatedServers> => {
    const { data } = await axios.get<PaginatedResponse<any>>(
        `/api/admin/address-block-groups/${addressBlockGroupId}/compatible-servers`,
        {
            params: {
                ...withQueryBuilderParams(params),
                include: include?.join(','),
            },
        }
    )

    return {
        items: data.items.map(rawDataToServer),
        pagination: data.pagination,
    }
}

export default getCompatibleServers
