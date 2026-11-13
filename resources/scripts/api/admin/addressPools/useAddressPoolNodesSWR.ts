import { useParams } from 'react-router-dom'
import useSWR from 'swr'

import getAttachedNodes, {
    QueryParams,
} from '@/api/admin/addressPools/getAttachedNodes'
import { NodeResponse } from '@/api/admin/nodes/getNodes'

const useAddressPoolNodesSWR = (
    poolId: number,
    { page, query, ...params }: QueryParams
) => {
    return useSWR<NodeResponse>(
        // No pool yet (EditPoolModal is mounted while closed, with -1): don't
        // ask for one. It used to request /address-pools/-1/attached-nodes and
        // get a 404 on every IPAM page load.
        poolId > 0 ? ['admin.address-pools.nodes', poolId, page, query] : null,
        () =>
            getAttachedNodes(poolId, {
                page,
                query,
                ...params,
            })
    )
}

export default useAddressPoolNodesSWR
