import getServers, { QueryParams, ServerResponse } from '@/api/admin/servers/getServers';
import useSWR from 'swr';

const useServersSWR = ({page, nodeId, userId, ...params}: QueryParams) => {
    return useSWR<ServerResponse>(['admin:servers', page, nodeId, userId], () => getServers({page, nodeId, userId, ...params}))
}

export default useServersSWR