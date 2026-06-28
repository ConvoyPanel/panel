import type { PaginatedAddresses, Address } from '@/types/address.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'
import { rawDataToAddress } from '@/api/transformers/address.ts'

export type AddressQueryParams = QueryBuilderParams<'ip' | 'server_id'>

export type AddressInclude = 'server' | 'addressBlock'

const getAddresses = async (
    blockGroupId: number,
    blockId: number,
    params: AddressQueryParams,
    include?: AddressInclude[]
): Promise<PaginatedAddresses> => {
    const { data } = await axios.get<PaginatedResponse<Address>>(
        `/api/admin/address-block-groups/${blockGroupId}/address-blocks/${blockId}/addresses`,
        {
            params: {
                ...withQueryBuilderParams(params),
                include: include?.join(','),
            },
        }
    )

    return {
        items: data.items.map(rawDataToAddress),
        pagination: data.pagination,
    }
}

export default getAddresses
