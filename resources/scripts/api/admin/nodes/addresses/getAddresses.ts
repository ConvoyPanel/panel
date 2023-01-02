import http, { getPaginationSet, PaginatedResult } from '@/api/http'
import { Address, AddressType, rawDataToAddressObject } from '@/api/server/getServer'

export type AddressIncludes = 'server'

export type AddressResponse = PaginatedResult<Address>

export interface QueryParams {
    serverId?: number | null
    type?: AddressType
    address?: string
    query?: string
    page?: number
    perPage?: number
    includes?: Array<AddressIncludes>
}

const getAddresses = async (
    nodeId: number,
    { serverId, type, address, query, perPage = 50, includes, ...params }: QueryParams
): Promise<AddressResponse> => {
    const { data } = await http.get(`/api/admin/nodes/${nodeId}/addresses`, {
        params: {
            'filter[server_id]': serverId === null ? '' : serverId,
            'filter[type]': type,
            'filter[address]': address,
            'filter[*]': query,
            'per_page': perPage,
            'includes': includes?.join(','),
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToAddressObject),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddresses
