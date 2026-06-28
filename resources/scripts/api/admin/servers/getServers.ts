import { PaginatedServers, Server } from '@/types/server'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'

import axios from '@/lib/axios'
import type { PaginatedResponse } from '@/lib/api'

import { rawDataToServer } from '@/api/transformers/server'

export type ServerQueryParams = QueryBuilderParams<
    '*' | 'name' | 'hostname' | 'node_id' | 'user_id'
>

const getServers = async (params: ServerQueryParams): Promise<PaginatedServers> => {
    const { data } = await axios.get<PaginatedResponse<Server>>(
        '/api/admin/servers',
        { params: withQueryBuilderParams(params) },
    )

    return {
        items: data.items.map(rawDataToServer),
        pagination: data.pagination,
    }
}

export default getServers
