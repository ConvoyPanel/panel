import { PaginatedNetworkInterfaces } from '@/types/network-interface.ts'
import { keepPreviousData, useQuery } from '@tanstack/react-query'

import getAttachedNodes, {
    AttachedNodesQueryParams,
} from '@/api/admin/addressBlockGroups/getAttachedNodes.ts'

export const getKey = (
    addressBlockGroupId: number | null | undefined,
    params: AttachedNodesQueryParams
) => ['address-block-groups.nodes', addressBlockGroupId, params]

const useAttachedNodes = (
    addressBlockGroupId: number | null | undefined,
    params: AttachedNodesQueryParams
) => {
    return useQuery<PaginatedNetworkInterfaces>({
        queryKey: getKey(addressBlockGroupId, params),
        queryFn: () => getAttachedNodes(addressBlockGroupId!, params),
        enabled: !!addressBlockGroupId,
        placeholderData: keepPreviousData,
    })
}

export default useAttachedNodes
