import axios from '@/lib/axios.ts'

import { rawDataToAddress } from '@/api/transformers/address.ts'

const updateAddress = async (
    blockGroupId: number,
    blockId: number,
    addressId: number,
    serverId: number | null
) => {
    const { data: { data } } = await axios.patch(
        `/api/admin/address-block-groups/${blockGroupId}/address-blocks/${blockId}/addresses/${addressId}`,
        {
            server_id: serverId,
        }
    )

    return rawDataToAddress(data)
}

export default updateAddress
