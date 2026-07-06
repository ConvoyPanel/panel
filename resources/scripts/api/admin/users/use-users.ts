import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getUser from '@/api/admin/users/getUser.ts'
import getUsers, { UserQueryParams } from '@/api/admin/users/getUsers.ts'

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

const useUsers = (params: UserQueryParams) => useQuery(userQueries.list(params))

export default useUsers
