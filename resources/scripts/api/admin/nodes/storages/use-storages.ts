import { useParams } from '@tanstack/react-router'
import { queryOptions, useQuery } from '@tanstack/react-query'

import getStorages from '@/api/admin/nodes/storages/getStorages.ts'

export const storageQueries = {
    all: (nodeId: number) => ['admin', 'nodes', nodeId, 'storages'] as const,
    list: (nodeId: number) =>
        queryOptions({
            queryKey: storageQueries.all(nodeId),
            queryFn: () => getStorages(nodeId),
            enabled: !!nodeId,
        }),
}

const useStorages = (nodeId?: number) => {
    const params = useParams({ strict: false })
    const id = nodeId ?? Number(params.nodeId)

    return useQuery(storageQueries.list(id))
}

export default useStorages
