import { PaginatedAdminUsers } from '@/types/admin/user.ts'
import useSWR from '@/lib/swr'

import getUsers, { UserQueryParams } from '@/api/admin/users/getUsers.ts'

export const getKey = (params: UserQueryParams) => ['users', params]

const useUsersSWR = (params: UserQueryParams) => {
    return useSWR<PaginatedAdminUsers>(getKey(params), () => getUsers(params))
}

export default useUsersSWR
