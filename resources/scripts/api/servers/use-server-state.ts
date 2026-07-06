import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import getState from '@/api/servers/getState.ts'


export const getKey = (uuid: string) => ['server.state', uuid]

const useServerState = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useQuery({
        queryKey: getKey(serverUuid),
        queryFn: () => getState(serverUuid),
        refetchInterval: 50,
    })
}

export default useServerState
