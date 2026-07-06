import { useParams } from '@tanstack/react-router'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import {
    rawDataToNodeStorage,
    rawDataToStorageProxmox,
} from '@/api/transformers/storage.ts'
import { apiFetch, type DataResponse } from '@/lib/api'
import { NodeStorage } from '@/types/storage.ts'
import StorageController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/StorageController'

export const storageSchema = z
    .object({
        displayName: z.string().max(40).optional(),
        description: z.string().max(191),
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
            })
        }
    })

// StorageController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute = StorageController.index['/api/admin/nodes/{node}/storages']
const proxmoxRoute =
    StorageController.fetchFromProxmox[
        '/api/admin/nodes/{node}/storages/proxmox'
    ]
const storeRoute = StorageController.store['/api/admin/nodes/{node}/storages']
const backupOrderRoute =
    StorageController.updateBackupOrder[
        '/api/admin/nodes/{node}/storages/backup-order'
    ]
const updateRoute =
    StorageController.update['/api/admin/nodes/{node}/storages/{storage}']
const destroyRoute =
    StorageController.destroy['/api/admin/nodes/{node}/storages/{storage}']

export const getStorages = async (nodeId: number): Promise<NodeStorage[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(indexRoute(nodeId))

    return data.map(rawDataToNodeStorage)
}

export const getStoragesProxmox = async (nodeId: number) => {
    const { data } = await apiFetch<DataResponse<any[]>>(proxmoxRoute(nodeId))

    return data.map(rawDataToStorageProxmox)
}

export const storageQueries = {
    all: (nodeId: number) => ['admin', 'nodes', nodeId, 'storages'] as const,
    list: (nodeId: number) =>
        queryOptions({
            queryKey: storageQueries.all(nodeId),
            queryFn: () => getStorages(nodeId),
            enabled: !!nodeId,
        }),
}

export const useStorages = (nodeId?: number) => {
    const params = useParams({ strict: false })
    const id = nodeId ?? Number(params.nodeId)

    return useQuery(storageQueries.list(id))
}

export const createStorage = async (
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
) =>
    rawDataToNodeStorage(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(nodeId), {
                body: {
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
                },
            })
        ).data
    )

export const updateStorage = async (
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
) =>
    rawDataToNodeStorage(
        (
            await apiFetch<DataResponse<unknown>>(
                updateRoute({ node: nodeId, storage: storageId }),
                {
                    body: {
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
                    },
                }
            )
        ).data
    )

export const updateBackupOrder = async (
    nodeId: number,
    storageIds: number[]
): Promise<NodeStorage[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(
        backupOrderRoute(nodeId),
        { body: { ids: storageIds } }
    )

    return data.map(rawDataToNodeStorage)
}

export const deleteStorage = async (
    nodeId: number,
    storageId: number
): Promise<void> => {
    await apiFetch(destroyRoute({ node: nodeId, storage: storageId }))
}
