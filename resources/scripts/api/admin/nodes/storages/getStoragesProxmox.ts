import axios from '@/lib/axios.ts'

import { rawDataToStorageProxmox } from '@/api/transformers/storage.ts'

const getStoragesProxmox = async (nodeId: number) => {
    const {
        data: { data },
    } = await axios.get(`/api/admin/nodes/${nodeId}/storages/proxmox`)

    return data.map(rawDataToStorageProxmox)
}

export default getStoragesProxmox
