import {
    AdminServerBuild,
    rawDataToAdminServer,
} from '@/api/admin/servers/getServer'
import http, { PaginatedResult, getPaginationSet } from '@/api/http'


export type ServerInclude = 'user' | 'node'

export interface QueryParams {
    addressPoolId?: number | null
    nodeId?: number | null
    userId?: number | null
    query?: string | null
    page?: number | null
    perPage?: number | null
    include?: ServerInclude[] | null
}

export type ServerResponse = PaginatedResult<AdminServerBuild>

const getServers = async ({
    addressPoolId,
    nodeId,
    userId,
    query,
    perPage = 50,
    include,
    ...params
}: QueryParams): Promise<ServerResponse> => {
    const { data } = await http.get('/api/admin/servers', {
        params: {
            'filter[address_pool_id]': addressPoolId,
            'filter[node_id]': nodeId,
            'filter[user_id]': userId,
            'filter[*]': query,
            'include': include?.join(','),
            'per_page': perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToAdminServer),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getServers