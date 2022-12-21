import http, { getPaginationSet, PaginatedResult } from '@/api/http'
import { rawDataToServerObject, Server } from '@/api/server/getServer'

export interface QueryParams {
    nodeId?: number
    userId?: number
    query?: string
    page?: number
    perPage?: number
}

export type ServerResponse = PaginatedResult<Server>

const getServers = async ({
    nodeId,
    userId,
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<ServerResponse> => {
    const { data } = await http.get('/api/admin/servers', {
        params: {
            'filter[node_id]': nodeId,
            'filter[user_id]': userId,
            'filter[name]': query,
            per_page: perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToServerObject),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getServers