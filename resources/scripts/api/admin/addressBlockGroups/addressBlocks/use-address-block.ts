import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import { addressBlockQueries } from '@/api/admin/addressBlockGroups/addressBlocks/use-address-blocks.ts'

export const preloadAddressBlock = (blockGroupId: number, blockId: number) =>
    queryClient.prefetchQuery(
        addressBlockQueries.detail(blockGroupId, blockId)
    )

const useAddressBlock = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useQuery(
        addressBlockQueries.detail(
            params.addressBlockGroupId,
            params.addressBlockId
        )
    )
}

export default useAddressBlock
