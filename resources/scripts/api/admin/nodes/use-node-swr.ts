import { useParams } from '@tanstack/react-router'
import useSWR, { preload } from 'swr'

import getNode from '@/api/admin/nodes/getNode.ts'

export const getKey = (id: number) => ['node', id]

export const preloadNode = async (id: number) => {
    await preload(getKey(id), () => getNode(id))
}

const useNodeSWR = (id?: number | null) => {
    const params = useParams({ strict: false }) as { nodeId?: number }
    const nodeId = (id ?? params.nodeId)

    return useSWR(
        typeof nodeId === 'number' ? getKey(nodeId) : null,
        () => getNode(nodeId as number)
    )
}

export default useNodeSWR
