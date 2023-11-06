import useSWR from 'swr'

import getServers, {
    QueryParams,
    ServerResponse,
} from '@/api/admin/servers/getServers'


const useServersSWR = ({
    page,
    nodeId,
    userId,
    query,
    ...params
}: QueryParams) => {
    return useSWR<ServerResponse>(
        ['admin:servers', page, query, nodeId, userId],
        () => getServers({ page, query, nodeId, userId, ...params })
    )
}

export default useServersSWR