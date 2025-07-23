import { PaginatedServers } from '@/types/server'
import {
    FractalPaginatedResponse,
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http'

import axios from '@/lib/axios'

import { rawDataToServer } from '@/api/transformers/server'

export type ServerQueryParams = QueryBuilderParams<
    '*' | 'name' | 'hostname' | 'node_id' | 'user_id'
>

const getServers = async (
    params: ServerQueryParams,
): Promise<PaginatedServers> => {
    const { data } = await axios.get<FractalPaginatedResponse>(
        '/api/admin/servers',
        {
            params: withQueryBuilderParams(params),
        },
    )

    return {
        items: data.data.map(rawDataToServer),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getServers
