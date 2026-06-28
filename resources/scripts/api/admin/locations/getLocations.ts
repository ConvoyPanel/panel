import type { Location, PaginatedLocations } from '@/types/location.ts'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http.ts'

import axios from '@/lib/axios.ts'
import type { PaginatedResponse } from '@/lib/api.ts'

const getLocations = async (
    params: QueryBuilderParams<'*' | 'short_code'>
): Promise<PaginatedLocations> => {
    const { data } = await axios.get<PaginatedResponse<Location>>(
        '/api/admin/locations',
        {
            params: withQueryBuilderParams(params),
        }
    )

    return {
        items: data.items,
        pagination: data.pagination,
    }
}

export default getLocations
