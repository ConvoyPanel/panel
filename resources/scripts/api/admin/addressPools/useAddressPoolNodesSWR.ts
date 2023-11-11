import { useParams } from 'react-router-dom'
import useSWR from 'swr'

import getNodesAllocatedTo, {
    QueryParams,
} from '@/api/admin/addressPools/getNodesAllocatedTo'
import { NodeResponse } from '@/api/admin/nodes/getNodes'

const useAddressPoolNodesSWR = (
    poolId: number,
    { page, query, ...params }: QueryParams
) => {
    return useSWR<NodeResponse>(
        ['admin.address-pools.nodes', poolId, page, query],
        () =>
            getNodesAllocatedTo(poolId, {
                page,
                query,
                ...params,
            })
    )
}

export default useAddressPoolNodesSWR
