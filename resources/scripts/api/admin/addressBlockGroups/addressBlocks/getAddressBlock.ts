import axios from '@/lib/axios.ts'

import { rawDataToAddressBlock } from '@/api/transformers/address-block.ts'

const getAddressBlock = async (blockGroupId: number, blockId: number) => {
    const {
        data: { data },
    } = await axios.get(
        `/api/admin/address-block-groups/${blockGroupId}/address-blocks/${blockId}`
    )

    return rawDataToAddressBlock(data)
}

export default getAddressBlock
