import { PaginatedAddressBlockGroups } from '@/types/address-block-group.ts'
import {
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http.ts'

import axios from '@/lib/axios.ts'

import { rawDataToAddressBlockGroup } from '@/api/transformers/address-block-group.ts'

export type AddressBlockGroupQueryParams = QueryBuilderParams<
    '*' | 'name' | 'description'
>

const getAddressBlockGroups = async (
    params: AddressBlockGroupQueryParams
): Promise<PaginatedAddressBlockGroups> => {
    const { data } = await axios.get('/api/admin/address-block-groups', {
        params: withQueryBuilderParams(params),
    })

    return {
        items: data.data.map(rawDataToAddressBlockGroup),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getAddressBlockGroups
