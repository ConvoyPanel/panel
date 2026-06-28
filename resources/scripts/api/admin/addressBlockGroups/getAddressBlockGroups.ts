import type { PaginatedAddressBlockGroups, AddressBlockGroup } from '@/types/address-block-group.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

export type AddressBlockGroupQueryParams = QueryBuilderParams<
    '*' | 'name' | 'description'
>

const getAddressBlockGroups = async (
    params: AddressBlockGroupQueryParams
): Promise<PaginatedAddressBlockGroups> => {
    const { data } = await axios.get<PaginatedResponse<AddressBlockGroup>>(
        '/api/admin/address-block-groups',
        { params: withQueryBuilderParams(params) }
    )

    return {
        items: data.items,
        pagination: data.pagination,
    }
}

export default getAddressBlockGroups
