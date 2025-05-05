import axios from '@/lib/axios'


const deleteStorage = async (nodeId: number, storageId: number) => {
    await axios.delete(`/api/admin/nodes/${nodeId}/storages/${storageId}`)
}

export default deleteStorage