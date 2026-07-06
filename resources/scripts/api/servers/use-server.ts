import { useParams } from '@tanstack/react-router'
import { useQuery, type UseQueryOptions } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getServer from '@/api/servers/getServer.ts'
import { Server } from '@/types/server'


export const getKey = (uuid: string) => ['server', uuid]

export const preloadServer = (uuid: string) =>
    queryClient.prefetchQuery({ queryKey: getKey(uuid), queryFn: () => getServer(uuid) })

const useServer = (uuid?: string, options?: Partial<UseQueryOptions<Server>>) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        queryKey: getKey(serverUuid),
        queryFn: () => getServer(serverUuid),
        ...options,
    })
}

export default useServer
