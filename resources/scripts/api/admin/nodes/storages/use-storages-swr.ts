import useSWR from 'swr'

import getStorages from '@/api/admin/nodes/storages/getStorages.ts'
import { useParams } from '@tanstack/react-router'

export const getKey = (nodeId: number) => ['node.storages', nodeId]

const useStoragesSWR = (nodeId?: number) => {
    const params = useParams({ strict: false }) as { nodeId?: number }
    const id = nodeId ?? params.nodeId

    return useSWR(
        typeof id === 'number' ? getKey(id) : null,
        () => getStorages(id as number)
    )
}

export default useStoragesSWR
