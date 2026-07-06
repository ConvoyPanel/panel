import {
    keepPreviousData,
    queryOptions,
    useQuery,
} from '@tanstack/react-query'

import { rawDataToAdminUser } from '@/lib/transformers/admin/user.ts'
import { apiFetch, type DataResponse, type PaginatedResponse } from '@/lib/api'
import type { AdminUser, PaginatedAdminUsers } from '@/types/admin/user'
import {
    type QueryBuilderParams,
    withQueryBuilderParams,
} from '@/utils/http'
import UserController from '@/wayfinder/actions/App/Http/Controllers/Admin/UserController'

export type UserQueryParams = QueryBuilderParams<'*' | 'name' | 'email' | 'id'>

// UserController is served under both the panel (`/api/admin`) and Application
// (`/api/application`) prefixes, so Wayfinder emits URI-keyed dictionaries —
// reference the admin route explicitly.
const indexRoute = UserController.index['/api/admin/users']
const showRoute = UserController.show['/api/admin/users/{user}']

export const getUsers = async (
    params: UserQueryParams
): Promise<PaginatedAdminUsers> => {
    const res = await apiFetch<PaginatedResponse<AdminUser>>(indexRoute(), {
        params: withQueryBuilderParams(params),
    })

    return { items: res.items, pagination: res.pagination }
}

const getUser = async (id: number): Promise<AdminUser> =>
    rawDataToAdminUser((await apiFetch<DataResponse<unknown>>(showRoute(id))).data)

export const userQueries = {
    all: () => ['admin', 'users'] as const,
    lists: () => [...userQueries.all(), 'list'] as const,
    list: (params: UserQueryParams) =>
        queryOptions({
            queryKey: [...userQueries.lists(), params] as const,
            queryFn: () => getUsers(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...userQueries.all(), 'detail'] as const,
    detail: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...userQueries.details(), id] as const,
            queryFn: () => getUser(id!),
            enabled: id != null,
        }),
}

export const useUsers = (params: UserQueryParams) =>
    useQuery(userQueries.list(params))

export const useUser = (id: number | null) => useQuery(userQueries.detail(id))
