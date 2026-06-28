import { keepPreviousData, queryOptions } from '@tanstack/react-query'

import { apiFetch, type PaginatedResponse } from '@/lib/api'
import type { PaginatedResult } from '@/utils/http.ts'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/ServerController'

export type ServerData = App.Data.Server.ServerData

export type ServerListParams = {
    page?: number
    filter?: { name?: string }
    perPage?: number
}

const buildListParams = (params: ServerListParams = {}) => ({
    page: params.page,
    'filter[name]': params.filter?.name,
    per_page: params.perPage,
})

const toPaginatedResult = <T>(res: PaginatedResponse<T>): PaginatedResult<T> => ({
    items: res.items,
    pagination: res.pagination,
})

export const serverQueries = {
    all: () => ['servers'] as const,

    list: (params: ServerListParams = {}) =>
        queryOptions({
            queryKey: [...serverQueries.all(), 'list', params] as const,
            queryFn: async () => {
                const res = await apiFetch<PaginatedResponse<ServerData>>(
                    ServerController.index(),
                    { params: buildListParams(params) }
                )
                return toPaginatedResult(res)
            },
            placeholderData: keepPreviousData,
        }),

    detail: (uuid: string) =>
        queryOptions({
            queryKey: [...serverQueries.all(), 'detail', uuid] as const,
            queryFn: () => apiFetch<ServerData>(ServerController.show(uuid)),
        }),
}
