import axios from '@/lib/axios.ts'

const deleteAddress = async (
    blockGroupId: number,
    blockId: number,
    addressId: number
) => {
    await axios.delete(
        `/api/admin/address-block-groups/${blockGroupId}/address-blocks/${blockId}/addresses/${addressId}`
    )
}

export default deleteAddress
