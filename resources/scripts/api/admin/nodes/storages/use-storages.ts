import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import getStorages from '@/api/admin/nodes/storages/getStorages.ts'

export const getKey = (nodeId: number) => ['node.storages', nodeId]

const useStorages = (nodeId?: number) => {
    const params = useParams({ strict: false })
    const id = nodeId ?? Number(params.nodeId)

    return useQuery({
        queryKey: getKey(id),
        queryFn: () => getStorages(id),
        enabled: !!id,
    })
}

export default useStorages
