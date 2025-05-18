import { PaginatedAddresses } from '@/types/address.ts'
import { getPaginationSet, QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import { rawDataToAddress } from '@/api/transformers/address.ts'

export type AddressQueryParams = QueryBuilderParams<'ip' | 'server_id'>

export type AddressInclude = 'server' | 'addressBlock'

const getAddresses = async (
    blockGroupId: number,
    blockId: number,
    params: AddressQueryParams,
    include?: AddressInclude[]
): Promise<PaginatedAddresses> => {
    const {
        data,
    } = await axios.get(
        `/api/admin/address-block-groups/${blockGroupId}/address-blocks/${blockId}/addresses`,
        {
            params: {
                ...withQueryBuilderParams(params),
                include: include?.join(','),
            },
        }
    )

    return {
        items: data.data.map(rawDataToAddress),
        pagination: getPaginationSet(data.meta.pagination)
    }
}

export default getAddresses
