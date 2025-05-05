import axios from '@/lib/axios.ts'

const deleteNetworkInterface = async (nodeId: number, interfaceId: number) => {
    await axios.delete(
        `/api/admin/nodes/${nodeId}/network-interfaces/${interfaceId}`
    )
}

export default deleteNetworkInterface
