import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getServerResources from '@/api/servers/getServerResources'

export const getKey = (uuid: string) => ['server', uuid, 'resources']

const useServerResources = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid), () => getServerResources(serverUuid), {
        refreshInterval: 60000, // Refresh every minute
    })
}

export default useServerResources
