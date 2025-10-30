import { PaginatedAdminUsers } from '@/types/admin/user'
import {
    FractalPaginatedResponse,
    QueryBuilderParams,
    getPaginationSet,
    withQueryBuilderParams,
} from '@/utils/http'

import axios from '@/lib/axios'

import { rawDataToAdminUser } from '@/api/transformers/admin/user'

export type UserQueryParams = QueryBuilderParams<'*' | 'name' | 'email' | 'id'>

const getUsers = async (
    params: UserQueryParams
): Promise<PaginatedAdminUsers> => {
    const { data } = await axios.get<FractalPaginatedResponse>(
        '/api/admin/users',
        {
            params: withQueryBuilderParams(params),
        }
    )

    return {
        items: data.data.map(rawDataToAdminUser),
        pagination: getPaginationSet(data.meta.pagination),
    }
}

export default getUsers
