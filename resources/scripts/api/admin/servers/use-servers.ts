import { PaginatedServers } from '@/types/server'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getServers, { ServerQueryParams } from '@/api/admin/servers/getServers'

export const getKey = (params: ServerQueryParams) => ['servers', params]

const useServers = (params: ServerQueryParams) => {
    return useQuery<PaginatedServers>({
        queryKey: getKey(params),
        queryFn: () => getServers(params),
        placeholderData: keepPreviousData,
    })
}

export default useServers
