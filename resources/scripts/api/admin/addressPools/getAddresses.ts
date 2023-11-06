import {
    AddressInclude,
    AddressResponse,
} from '@/api/admin/nodes/addresses/getAddresses'
import http, { getPaginationSet } from '@/api/http'
import { rawDataToAddress } from '@/api/server/getServer'

export interface QueryParams {
    query?: string
    page?: number
    perPage?: number
    include?: Array<AddressInclude>
}

const getAddresses = async (
    addressPoolId: number,
    { query, page, perPage = 50, include }: QueryParams
): Promise<AddressResponse> => {
    const { data } = await http.get(
        `/api/admin/address-pools/${addressPoolId}/addresses`,
        {
            params: {
                'filter[*]': query,
                page,
                'per_page': perPage,
                'include': include?.join(','),
            },
        }
    )

    return {
        items: data.data.map(rawDataToAddress),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddresses