import type { AdminUser, PaginatedAdminUsers } from '@/types/admin/user'
import { type QueryBuilderParams, withQueryBuilderParams } from '@/utils/http'

import axios from '@/lib/axios'
import type { PaginatedResponse } from '@/lib/api'

export type UserQueryParams = QueryBuilderParams<'*' | 'name' | 'email' | 'id'>

const getUsers = async (params: UserQueryParams): Promise<PaginatedAdminUsers> => {
    const { data } = await axios.get<PaginatedResponse<AdminUser>>('/api/admin/users', {
        params: withQueryBuilderParams(params),
    })

    return {
        items: data.items,
        pagination: data.pagination,
    }
}

export default getUsers
