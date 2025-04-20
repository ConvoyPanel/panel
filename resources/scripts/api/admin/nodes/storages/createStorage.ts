import { z } from 'zod'

import axios from '@/lib/axios.ts'

import { rawDataToNodeStorage } from '@/api/transformers/storage.ts'

export const storageSchema = z
    .object({
        displayName: z.string().max(40).optional(),
        description: z.string().max(191).nullable(),
        name: z.string().min(1).max(191),
        size: z.coerce.number().min(1),
        isShareable: z.boolean(),
        storesKvm: z.boolean(),
        storesLxc: z.boolean(),
        storesLxcTemplates: z.boolean(),
        storesBackups: z.boolean(),
        storesIso: z.boolean(),
        storesSnippets: z.boolean(),
    })
    .superRefine((data, ctx) => {
        if (data.isShareable && !data.displayName) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                path: ['displayName'],
                message: 'Display name is required when storage is shareable',
            });
        }
    })

const createStorage = async (
    nodeId: number,
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
    } = await axios.post(`/api/admin/nodes/${nodeId}/storages`, {
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

export default createStorage
