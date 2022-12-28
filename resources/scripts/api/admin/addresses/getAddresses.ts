import http, { getPaginationSet, PaginatedResult } from '@/api/http'
import { Address, AddressType, rawDataToAddressObject } from '@/api/server/getServer'

export type AddressResponse = PaginatedResult<Address>

export interface QueryParams {
    nodeId?: number
    type?: AddressType
    query?: string
    page?: number
    perPage?: number
}

const getAddresses = async ({
    nodeId,
    type,
    query,
    perPage = 50,
    ...params
}: QueryParams): Promise<AddressResponse> => {
    const { data } = await http.get('/api/admin/addresses', {
        params: {
            'filter[node_id]': nodeId,
            'filter[type]': type,
            'filter[address]': query,
            per_page: perPage,
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToAddressObject),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddresses