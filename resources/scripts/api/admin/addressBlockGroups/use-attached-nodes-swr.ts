import { PaginatedNodes } from '@/types/node.ts'
import useSWR from 'swr'

import getAttachedNodes, {
    AttachedNodesQueryParams,
} from '@/api/admin/addressBlockGroups/getAttachedNodes.ts'

export const getKey = (
    addressBlockGroupId: number | null | undefined,
    params: AttachedNodesQueryParams
) =>
    addressBlockGroupId ? ['address-block-groups.nodes', addressBlockGroupId, params] : null

const useAttachedNodesSWR = (
    addressBlockGroupId: number | null | undefined,
    params: AttachedNodesQueryParams
) => {
    return useSWR<PaginatedNodes>(
        getKey(addressBlockGroupId, params),
        () => getAttachedNodes(addressBlockGroupId!, params)
    )
}

export default useAttachedNodesSWR