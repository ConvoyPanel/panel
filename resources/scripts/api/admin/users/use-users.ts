import { PaginatedAdminUsers } from '@/types/admin/user.ts'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getUsers, { UserQueryParams } from '@/api/admin/users/getUsers.ts'

export const getKey = (params: UserQueryParams) => ['users', params]

const useUsers = (params: UserQueryParams) => {
    return useQuery<PaginatedAdminUsers>({
        queryKey: getKey(params),
        queryFn: () => getUsers(params),
        placeholderData: keepPreviousData,
    })
}

export default useUsers
