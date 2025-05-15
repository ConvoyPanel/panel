import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { addressBlockSchema } from '@/api/admin/addressBlockGroups/addressBlocks/createAddressBlock.ts'
import { rawDataToAddressBlock } from '@/api/transformers/address-block.ts'

const updateAddressBlock = async (
    addressBlockGroupId: number,
    addressBlockId: number,
    { version: _, ...params }: z.infer<typeof addressBlockSchema>
) => {
    const {
        data: { data },
    } = await axios.put(
        `/api/admin/address-block-groups/${addressBlockGroupId}/address-blocks/${addressBlockId}`,
        params
    )

    return rawDataToAddressBlock(data)
}

export default updateAddressBlock
