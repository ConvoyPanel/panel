import { useParams } from '@tanstack/react-router'
import useSWR, { preload, type SWRConfiguration } from 'swr'

import getServer from '@/api/servers/getServer.ts'
import { Server } from '@/types/server'


export const getKey = (uuid: string) => ['server', uuid]

export const preloadServer = async (uuid: string) => {
    await preload(getKey(uuid), () => getServer(uuid))
}

const useServerSWR = (uuid?: string, config?: SWRConfiguration<Server>) => {
    const params = useParams({ strict: false }) as { serverUuid: string }
    const serverUuid = uuid ?? params.serverUuid

    return useSWR(getKey(serverUuid), () => getServer(serverUuid), config)
}

export default useServerSWR
