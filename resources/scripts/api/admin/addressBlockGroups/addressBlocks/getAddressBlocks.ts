import type { PaginatedAddressBlocks, AddressBlock } from '@/types/address-block.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

export type AddressBlockQueryParams = QueryBuilderParams<
    | '*'
    | 'name'
    | 'description'
    | 'type'
    | 'base_ip'
    | 'gateway'
    | 'mac_address'
    | 'prefix_length_to'
    | 'prefix_length_from'
>

const getAddressBlocks = async (
    addressBlockGroupId: number,
    params: AddressBlockQueryParams
): Promise<PaginatedAddressBlocks> => {
    const { data } = await axios.get<PaginatedResponse<AddressBlock>>(
        `/api/admin/address-block-groups/${addressBlockGroupId}/address-blocks`,
        { params: withQueryBuilderParams(params) }
    )

    return {
        items: data.items,
        pagination: data.pagination,
    }
}

export default getAddressBlocks
