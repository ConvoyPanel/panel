import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { storageSchema } from '@/api/admin/nodes/storages/createStorage.ts'
import { rawDataToNodeStorage } from '@/api/transformers/storage.ts'

const updateStorage = async (
    nodeId: number,
    storageId: number,
    {
        displayName,
        description,
        name,
        size,
        isShareable,
        storesKvm,
        storesLxc,
        storesLxcTemplates,
        storesBackups,
        storesIso,
        storesSnippets,
    }: z.infer<typeof storageSchema>
) => {
    const {
        data: { data },
    } = await axios.put(`/api/admin/nodes/${nodeId}/storages/${storageId}`, {
        display_name: displayName,
        description,
        name,
        size,
        is_shareable: isShareable,
        stores_kvm: storesKvm,
        stores_lxc: storesLxc,
        stores_lxc_templates: storesLxcTemplates,
        stores_backups: storesBackups,
        stores_iso: storesIso,
        stores_snippets: storesSnippets,
    })

    return rawDataToNodeStorage(data)
}

export default updateStorage
