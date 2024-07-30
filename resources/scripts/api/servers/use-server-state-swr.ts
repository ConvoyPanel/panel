import { useParams } from '@tanstack/react-router'
import useSWR from 'swr'

import getState from '@/api/servers/getState.ts'


export const getKey = (uuid: string) => ['server.state', uuid]

const useServerStateSWR = (uuid?: string) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid), () => getState(serverUuid), {
        refreshInterval: 500,
    })
}

export default useServerStateSWR
