import { AdminUser } from '@/types/admin/user.ts'
import { useQuery } from '@tanstack/react-query'

import getUser from '@/api/admin/users/getUser.ts'

export const getKey = (id: number | null | undefined) => ['user', id]

const useUser = (id: number | null) => {
    return useQuery<AdminUser | null>({
        queryKey: getKey(id),
        queryFn: () => getUser(id!),
        enabled: id != null,
    })
}

export default useUser
