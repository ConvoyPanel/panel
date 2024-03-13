import { Optimistic } from '@/lib/swr'
import { useMatch } from 'react-router-dom'
import useSWR, { Key, SWRResponse } from 'swr'

import getUser from '@/api/admin/users/getUser'
import { User } from '@/api/admin/users/getUsers'


export const getKey = (id: number): Key => ['admin.users', id]

const useUserSWR = () => {
    const match = useMatch('/admin/users/:id/*')
    const id = parseInt(match!.params.id!)

    return useSWR<User>(getKey(id), () => getUser(id)) as Optimistic<
        SWRResponse<User, any>
    >
}

export default useUserSWR