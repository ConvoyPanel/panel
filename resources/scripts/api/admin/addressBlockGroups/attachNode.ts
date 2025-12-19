import axios from '@/lib/axios.ts'

const attachNode = async (
    addressBlockGroupId: number,
    networkInterfaceId: number
) => {
    await axios.post(
        `/api/admin/address-block-groups/${addressBlockGroupId}/nodes`,
        {
            network_interface_id: networkInterfaceId,
        }
    )
}

export default attachNode
