import useSWR from 'swr'

import getNodes, {NodeResponse, QueryParams} from '@/api/admin/nodes/getNodes'


const useNodesSWR = ({page, query, id, cotermId, ...params}: QueryParams) => {
    return useSWR<NodeResponse>(
        ['admin:nodes', page, query, id, cotermId],
        () => getNodes({page, query, id, cotermId, ...params})
    )
}

export default useNodesSWR