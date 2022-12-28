import http, { getPaginationSet, PaginatedResult } from '@/api/http'
import { Address, AddressType, rawDataToAddressObject } from '@/api/server/getServer'

export type AddressIncludes = 'server'

export type AddressResponse = PaginatedResult<Address>

export interface QueryParams {
    type?: AddressType
    query?: string
    page?: number
    perPage?: number
    includes?: Array<AddressIncludes>
}

const getAddresses = async (nodeId: number, {
    type,
    query,
    perPage = 50,
    includes,
    ...params
}: QueryParams): Promise<AddressResponse> => {
    const { data } = await http.get(`/api/admin/nodes/${nodeId}/addresses`, {
        params: {
            'filter[type]': type,
            'filter[address]': query,
            per_page: perPage,
            includes: includes?.join(','),
            ...params,
        },
    })

    return {
        items: data.data.map(rawDataToAddressObject),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddresses