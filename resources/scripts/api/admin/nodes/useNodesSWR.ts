import getNodes, { NodeResponse, QueryParams } from '@/api/admin/nodes/getNodes'
import useSWR from 'swr'

const useNodesSWR = ({ page, query, ...params }: QueryParams) => {
    return useSWR<NodeResponse>(['admin:nodes', page, query], () => getNodes({ page, query, ...params }))
}

export default useNodesSWR
