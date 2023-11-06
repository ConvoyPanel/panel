import useSWR from 'swr'

import getUsers, { QueryParams, UserResponse } from '@/api/admin/users/getUsers'

const useUsersSWR = ({ page, query, ...params }: QueryParams) => {
    return useSWR<UserResponse>(['admin:users', page, query], () =>
        getUsers({ page, query, ...params })
    )
}

export default useUsersSWR