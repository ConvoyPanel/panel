import useSWR, { preload } from 'swr'

import getServer from '@/api/servers/getServer.ts'


export const getKey = (uuid: string) => ['server', uuid]

export const preloadServer = async (uuid: string) => {
    await preload(getKey(uuid), () => getServer(uuid))
}

const useServerSWR = (uuid: string) => {
    return useSWR(getKey(uuid), () => getServer(uuid))
}

export default useServerSWR
