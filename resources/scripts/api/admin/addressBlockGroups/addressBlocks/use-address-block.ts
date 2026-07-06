import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'

import { queryClient } from '@/lib/query-client.ts'

import getAddressBlock from '@/api/admin/addressBlockGroups/addressBlocks/getAddressBlock.ts'

export const getKey = (blockId: number) => [
    'address-block-group.address-block',
    blockId,
]

export const preloadAddressBlock = (blockGroupId: number, blockId: number) =>
    queryClient.prefetchQuery({
        queryKey: getKey(blockId),
        queryFn: () => getAddressBlock(blockGroupId, blockId),
    })

const useAddressBlock = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useQuery({
        queryKey: getKey(params.addressBlockId),
        queryFn: () =>
            getAddressBlock(params.addressBlockGroupId, params.addressBlockId),
    })
}

export default useAddressBlock
