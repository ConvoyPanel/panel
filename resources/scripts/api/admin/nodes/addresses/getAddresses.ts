import http, { PaginatedResult, getPaginationSet } from '@/api/http'
import { Address, AddressType, rawDataToAddress } from '@/api/server/getServer'

export type AddressInclude = 'server'

export type AddressResponse = PaginatedResult<Address>

export interface QueryParams {
    serverId?: number | null
    type?: AddressType
    address?: string
    query?: string
    page?: number
    perPage?: number
    include?: Array<AddressInclude>
}

const getAddresses = async (
    nodeId: number,
    {
        serverId,
        type,
        address,
        query,
        perPage = 50,
        include,
        ...params
    }: QueryParams
): Promise<AddressResponse> => {
    const { data } = await http.get(`/api/admin/nodes/${nodeId}/addresses`, {
        params: {
            'filter[server_id]': serverId === null ? '' : serverId,
            'filter[type]': type,
            'filter[address]': address,
            'filter[*]': query,
            'per_page': perPage,
            'include': include?.join(','),
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToAddress),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddresses