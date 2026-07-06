import { queryOptions, useQuery } from '@tanstack/react-query'

import { rawDataToAuthenticatedUser } from '@/api/transformers/user.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { queryClient } from '@/lib/query-client.ts'
import SessionController from '@/wayfinder/actions/App/Http/Controllers/Client/SessionController'

export const getUser = async () => {
    const { data } = await apiFetch<DataResponse<any>>(SessionController())

    return rawDataToAuthenticatedUser(data)
}

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

export const useUser = () => useQuery(currentUserQueries.detail())
