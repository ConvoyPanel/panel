import { NodeStorage } from '@/types/storage.ts'

import axios from '@/lib/axios.ts'

import { rawDataToNodeStorage } from '@/api/transformers/storage.ts'

const updateBackupOrder = async (
    nodeId: number,
    storageIds: number[]
): Promise<NodeStorage[]> => {
    const {
        data: { data },
    } = await axios.put(`/api/admin/nodes/${nodeId}/storages/backup-order`, {
        ids: storageIds,
    })

    return data.map(rawDataToNodeStorage)
}

export default updateBackupOrder
