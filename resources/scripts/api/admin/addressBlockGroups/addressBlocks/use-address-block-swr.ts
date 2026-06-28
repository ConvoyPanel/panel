import { useParams } from '@tanstack/react-router'
import useSWR, { preload } from '@/lib/swr'

import getAddressBlock from '@/api/admin/addressBlockGroups/addressBlocks/getAddressBlock.ts'

export const getKey = (blockId: number) => [
    'address-block-group.address-block',
    blockId,
]

export const preloadAddressBlock = async (
    blockGroupId: number,
    blockId: number
) => {
    await preload(getKey(blockId), () => getAddressBlock(blockGroupId, blockId))
}

const useAddressBlockSWR = () => {
    const params = useParams({ strict: false }) as {
        addressBlockGroupId: number
        addressBlockId: number
    }

    return useSWR(getKey(params.addressBlockId), () =>
        getAddressBlock(params.addressBlockGroupId, params.addressBlockId)
    )
}

export default useAddressBlockSWR
