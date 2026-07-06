import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getUser from '@/api/auth/getUser.ts'


export const getKey = () => ['user']

export const cacheUser = async () => {
    const user = await getUser()

    queryClient.setQueryData(getKey(), user)
}

const useUser = () => {
    return useQuery({ queryKey: getKey(), queryFn: () => getUser() })
}

export default useUser
