import { keepPreviousData, queryOptions, useQuery } from '@tanstack/react-query'

import getServer from '@/api/admin/servers/getServer.ts'
import getServers, { ServerQueryParams } from '@/api/admin/servers/getServers'

export const serverQueries = {
    all: () => ['admin', 'servers'] as const,
    lists: () => [...serverQueries.all(), 'list'] as const,
    list: (params: ServerQueryParams) =>
        queryOptions({
            queryKey: [...serverQueries.lists(), params] as const,
            queryFn: () => getServers(params),
            placeholderData: keepPreviousData,
        }),
    details: () => [...serverQueries.all(), 'detail'] as const,
    detail: (id: number | null | undefined) =>
        queryOptions({
            queryKey: [...serverQueries.details(), id] as const,
            queryFn: () => getServer(id!),
            enabled: id != null,
        }),
}

const useServers = (params: ServerQueryParams) =>
    useQuery(serverQueries.list(params))

export default useServers
