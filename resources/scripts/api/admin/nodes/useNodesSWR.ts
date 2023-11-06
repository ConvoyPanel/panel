import useSWR from 'swr'

import getNodes, { NodeResponse, QueryParams } from '@/api/admin/nodes/getNodes'

const useNodesSWR = ({ page, query, id, ...params }: QueryParams) => {
    return useSWR<NodeResponse>(['admin:nodes', page, query, Boolean(id)], () =>
        getNodes({ page, query, id, ...params })
    )
}

export default useNodesSWR