import { Optimistic } from '@/lib/swr'
import { useParams } from 'react-router-dom'
import useSWR, { Key, SWRResponse } from 'swr'

import getNode from '@/api/admin/nodes/getNode'
import { Node } from '@/api/admin/nodes/getNodes'


export const getKey = (id: number): Key => ['admin.nodes', id]

const useNodeSWR = () => {
    const { nodeId } = useParams()
    const id = parseInt(nodeId!)

    return useSWR<Node>(getKey(id), () => getNode(id), {
        revalidateOnMount: false,
    }) as Optimistic<SWRResponse<Node, any>>
}

export default useNodeSWR