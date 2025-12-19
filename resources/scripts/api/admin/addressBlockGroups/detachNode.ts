import axios from '@/lib/axios.ts'

const detachNode = async (addressBlockGroupId: number, nodeId: number) => {
    await axios.delete(
        `/api/admin/address-block-groups/${addressBlockGroupId}/nodes/${nodeId}`
    )
}

export default detachNode
