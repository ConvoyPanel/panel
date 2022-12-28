import getNodes, { NodeResponse, QueryParams } from '@/api/admin/nodes/getNodes'
import useSWR from 'swr'

const useNodesSWR = ({page, ...params}: QueryParams) => {
    return useSWR<NodeResponse>(['admin:nodes', page], () => getNodes({page, ...params}))
}

export default useNodesSWR