import { PaginatedServers } from '@/types/server'
import useSWR from 'swr'

import getServers, { ServerQueryParams } from '@/api/admin/servers/getServers'

export const getKey = (params: ServerQueryParams) => ['servers', params]

const useServersSWR = (params: ServerQueryParams) => {
    return useSWR<PaginatedServers>(getKey(params), () => getServers(params))
}

export default useServersSWR