import useSWR from '@/lib/swr'

import getStorages from '@/api/admin/nodes/storages/getStorages.ts'
import { useParams } from '@tanstack/react-router'

export const getKey = (nodeId: number) => ['node.storages', nodeId]

const useStoragesSWR = (nodeId?: number) => {
    const params = useParams({ strict: false })
    const id = nodeId ?? Number(params.nodeId)

    return useSWR(
        id ? getKey(id) : null,
        () => getStorages(id)
    )
}

export default useStoragesSWR
