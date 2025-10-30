import { AdminUser } from '@/types/admin/user.ts'
import useSWR from 'swr'

import getUser from '@/api/admin/users/getUser.ts'

export const getKey = (id: number) => ['user', id]

const useUserSWR = (id: number | null) => {
    return useSWR<AdminUser | null>(id ? getKey(id) : null, () => getUser(id!))
}

export default useUserSWR
