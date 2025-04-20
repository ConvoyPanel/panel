import axios from '@/lib/axios.ts'

import { rawDataToNodeStorage } from '@/api/transformers/storage.ts'
import { NodeStorage } from '@/types/storage.ts'

const getStorages = async (nodeId: number): Promise<NodeStorage[]> => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/nodes/${nodeId}/storages`)

    return data.map(rawDataToNodeStorage)
}

export default getStorages
