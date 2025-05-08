import { PaginatedAddressBlocks } from '@/types/address-block.ts'
import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

import { rawDataToAddressBlock } from '@/api/transformers/address-block.ts'

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
    const { data } = await axios.get(
        `/api/admin/address-block-groups/${addressBlockGroupId}/address-blocks`,
        {
            params: withQueryBuilderParams(params),
        }
    )

    return {
        items: data.data.map(rawDataToAddressBlock),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddressBlocks
