import { useQuery } from '@tanstack/react-query'

import { AttachedNodesQueryParams } from '@/api/admin/addressBlockGroups/getAttachedNodes.ts'
import { addressBlockGroupQueries } from '@/api/admin/addressBlockGroups/use-address-block-groups.ts'

const useAttachedNodes = (
    addressBlockGroupId: number | null | undefined,
    params: AttachedNodesQueryParams
) => useQuery(addressBlockGroupQueries.nodes(addressBlockGroupId, params))

export default useAttachedNodes
