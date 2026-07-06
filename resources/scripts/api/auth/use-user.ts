import { queryOptions, useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getUser from '@/api/auth/getUser.ts'

export const currentUserQueries = {
    all: () => ['user'] as const,
    detail: () =>
        queryOptions({
            queryKey: currentUserQueries.all(),
            queryFn: () => getUser(),
        }),
}

export const cacheUser = async () => {
    const user = await getUser()

    queryClient.setQueryData(currentUserQueries.all(), user)
}

const useUser = () => useQuery(currentUserQueries.detail())

export default useUser
