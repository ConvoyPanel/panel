import useSWR from 'swr'

import getServers, {
    QueryParams,
    ServerResponse,
} from '@/api/admin/servers/getServers'


const useServersSWR = ({
    page,
    nodeId,
    userId,
    addressPoolId,
    query,
    ...params
}: QueryParams) => {
    return useSWR<ServerResponse>(
        ['admin:servers', page, query, nodeId, userId, addressPoolId],
        () =>
            getServers({
                page,
                query,
                nodeId,
                userId,
                addressPoolId,
                ...params,
            })
    )
}

export default useServersSWR