import getNodes, { NodeResponse, QueryParams } from '@/api/admin/nodes/getNodes'
import useSWR from 'swr'

const useNodesSWR = ({ page, query, id, ...params }: QueryParams) => {
    return useSWR<NodeResponse>(['admin:nodes', page, query, id], () => getNodes({ page, query, id, ...params }))
}

export default useNodesSWR
