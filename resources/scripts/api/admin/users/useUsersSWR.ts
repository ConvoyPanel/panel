import useSWR from 'swr'
import getUsers, { QueryParams, UserResponse } from '@/api/admin/users/getUsers'

const useUsersSWR = ({ page, ...params }: QueryParams) => {
    return useSWR<UserResponse>(['admin:users', page], () => getUsers({ page, ...params }))
}

export default useUsersSWR
