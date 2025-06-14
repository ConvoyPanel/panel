import axios from '@/lib/axios.ts'

import { rawDataToServer } from '@/api/transformers/server.ts'
import { PaginatedServers } from '@/types/server'
import { getPaginationSet, QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

export type CompatibleServerQueryParams = QueryBuilderParams<'*' | 'node_id' | 'user_id' | 'name'>

export type ServerInclude = 'node'

const getCompatibleServers = async (
    addressBlockGroupId: number,
    params: CompatibleServerQueryParams,
    include?: ServerInclude[]
): Promise<PaginatedServers> => {
    const { data } = await axios.get(
        `/api/admin/address-block-groups/${addressBlockGroupId}/compatible-servers`,
        {
            params: {
                ...withQueryBuilderParams(params),
                include: include?.join(','),
            },
        }
    )

    return {
        items: data.data.map(rawDataToServer),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getCompatibleServers