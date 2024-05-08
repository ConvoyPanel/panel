import { PaginatedServers } from '@/types/server.ts'
import useSWR from 'swr'

import getServers from '@/api/servers/getServers.ts'


export const getKey = (page?: number) => ['servers', page]

const useServersSWR = (page?: number) => {
    return useSWR<PaginatedServers>(getKey(page), () => getServers({ page }))
}

export default useServersSWR
