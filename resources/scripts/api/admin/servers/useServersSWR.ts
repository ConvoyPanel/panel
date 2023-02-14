import getServers, { QueryParams, ServerResponse } from '@/api/admin/servers/getServers';
import useSWR from 'swr';

const useServersSWR = ({page, nodeId, userId, query, ...params}: QueryParams) => {
    return useSWR<ServerResponse>(['admin:servers', page, query, nodeId, userId], () => getServers({page, query, nodeId, userId, ...params}))
}

export default useServersSWR