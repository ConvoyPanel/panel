import axios from '@/lib/axios.ts'

const deleteAddressBlock = async (
    addressBlockGroupId: number,
    addressBlockId: number
) => {
    await axios.delete(
        `/api/admin/address-block-groups/${addressBlockGroupId}/address-blocks/${addressBlockId}`
    )
}

export default deleteAddressBlock