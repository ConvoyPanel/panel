import http, { PaginatedResult, getPaginationSet } from '@/api/http'

export interface AddressPool {
    id: number
    name: string
    nodesCount: number
    addressesCount: number
}

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
}

export type AddressPoolResponse = PaginatedResult<AddressPool>

const getAddressPools = async ({
    query,
    page,
    perPage = 50,
}: QueryParams): Promise<AddressPoolResponse> => {
    const { data } = await http.get('/api/admin/address-pools', {
        params: {
            'filter[*]': query,
            page,
            'per_page': perPage,
        },
    })

    return {
        items: data.data.map(rawDataToAddressPool),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export const rawDataToAddressPool = (data: any): AddressPool => ({
    id: data.id,
    name: data.name,
    nodesCount: data.nodes_count,
    addressesCount: data.addresses_count,
})

export default getAddressPools