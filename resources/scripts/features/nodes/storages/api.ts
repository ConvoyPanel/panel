import {
    rawDataToNodeStorage,
    rawDataToStorageProxmox,
} from '@/features/nodes/transforms.ts'
import { NodeStorage } from '@/features/nodes/types.ts'
import IsoController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/IsoController'
import StorageController from '@/wayfinder/actions/App/Http/Controllers/Admin/Nodes/StorageController'
import ServerController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerController'
import StorageBackupController from '@/wayfinder/actions/App/Http/Controllers/Admin/StorageBackupController'
import StorageConsumerController from '@/wayfinder/actions/App/Http/Controllers/Admin/StorageConsumerController'
import StorageInventoryController from '@/wayfinder/actions/App/Http/Controllers/Admin/StorageInventoryController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'

/**
 * Registering a storage Proxmox already has.
 *
 * Only the operator's own decisions. Name, size and content types come from the
 * picked storage, so nothing here can drift from the host.
 */
export const registerStorageSchema = z.object({
    displayName: z.string().max(40).optional(),
    description: z.string().max(191),
    reservedBytes: z.coerce.number().min(0).nullable().optional(),
})

export const storageSchema = z.object({
    displayName: z.string().max(40).optional(),
    description: z.string().max(191),
    name: z.string().min(1).max(191),
    size: z.coerce.number().min(1),
    reservedBytes: z.coerce.number().min(0).nullable().optional(),
    storesKvm: z.boolean(),
    storesLxc: z.boolean(),
    storesLxcTemplates: z.boolean(),
    storesBackups: z.boolean(),
    storesIso: z.boolean(),
    storesSnippets: z.boolean(),
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
// Served under both the panel and Application prefixes, so reference the admin one.
const inventoryRoute = StorageInventoryController['/api/admin/storages']
const consumersRoute =
    StorageConsumerController['/api/admin/storages/{storage}/consumers']
const deleteBackupRoute =
    StorageBackupController.destroy['/api/admin/backups/{backup}']
// An ISO's delete route is node-scoped and a server's is not; both live here
// because the storage detail page is the only screen that calls them.
const deleteIsoRoute =
    IsoController.destroy['/api/admin/nodes/{node}/isos/{iso}']
const deleteServerRoute =
    ServerController.destroy['/api/admin/servers/{server}']
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

/**
 * Every storage across every node, with recorded capacity only -- the fleet page
 * cannot make a live Proxmox call per node.
 */
export const getStorageInventory = async (): Promise<NodeStorage[]> => {
    const { data } = await apiFetch<DataResponse<any[]>>(inventoryRoute())

    return data.map(rawDataToNodeStorage)
}

export type StorageConsumers = App.Data.Storage.StorageConsumersData
export type StorageConsumer = App.Data.Storage.StorageConsumerData

/** The servers, backups and ISOs occupying a storage, largest first. */
export const getStorageConsumers = async (
    storageId: number
): Promise<StorageConsumers> =>
    (await apiFetch<DataResponse<StorageConsumers>>(consumersRoute(storageId)))
        .data

export const storageConsumersQuery = (storageId: number) =>
    queryOptions({
        queryKey: ['admin', 'storages', storageId, 'consumers'] as const,
        queryFn: () => getStorageConsumers(storageId),
        enabled: !!storageId,
    })

export const deleteBackup = async (uuid: string): Promise<void> => {
    await apiFetch(deleteBackupRoute(uuid))
}

export const deleteIso = async (
    nodeId: number,
    uuid: string
): Promise<void> => {
    await apiFetch(deleteIsoRoute([nodeId, uuid]))
}

export const deleteServer = async (uuid: string): Promise<void> => {
    await apiFetch(deleteServerRoute(uuid))
}

export const storageInventoryQuery = queryOptions({
    queryKey: ['admin', 'storages'] as const,
    queryFn: getStorageInventory,
})

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
        reservedBytes,
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
                    reserved_bytes: reservedBytes,
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
        reservedBytes,
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
                        reserved_bytes: reservedBytes,
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
