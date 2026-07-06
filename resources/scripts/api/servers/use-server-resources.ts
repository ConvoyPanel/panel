import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import getServerResources from '@/api/servers/getServerResources'

export const getKey = (uuid: string) => ['server', uuid, 'resources']

const useServerResources = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        queryKey: getKey(serverUuid),
        queryFn: () => getServerResources(serverUuid),
        refetchInterval: 60000, // Refresh every minute
    })
}

export default useServerResources
