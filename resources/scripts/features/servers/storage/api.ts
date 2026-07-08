import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'
import ResourceController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/ResourceController'

export interface StorageUsage {
    usedBytes: number
    totalBytes: number
}

/** Live filesystem usage (via the guest agent); totals are 0 when the agent is unavailable. */
export const getStorageUsage = async (uuid: string): Promise<StorageUsage> => {
    const { data } = await apiFetch<
        DataResponse<{ used_bytes: number; total_bytes: number }>
    >(ResourceController(uuid))

    return { usedBytes: data.used_bytes, totalBytes: data.total_bytes }
}

export type BootDevice = App.Data.Server.Proxmox.Config.DiskData

export interface BootOrder {
    bootOrder: BootDevice[]
    unusedDevices: BootDevice[]
}

export const getBootOrder = async (uuid: string): Promise<BootOrder> => {
    const { data } = await apiFetch<
        DataResponse<App.Data.Server.BootOrderData>
    >(SettingsController.getBootOrder(uuid))

    return { bootOrder: data.bootOrder, unusedDevices: data.unusedDevices }
}

export const updateBootOrder = async (
    uuid: string,
    order: string[]
): Promise<void> => {
    await apiFetch(SettingsController.updateBootOrder(uuid), {
        body: { order },
    })
}

export const storageQueries = {
    usage: (uuid: string) =>
        queryOptions({
            queryKey: ['servers', uuid, 'storage-usage'] as const,
            queryFn: () => getStorageUsage(uuid),
            retry: false,
        }),
    bootOrder: (uuid: string) =>
        queryOptions({
            queryKey: ['servers', uuid, 'boot-order'] as const,
            queryFn: () => getBootOrder(uuid),
        }),
}

export const useStorageUsage = (uuid: string) =>
    useQuery(storageQueries.usage(uuid))

export const useBootOrder = (uuid: string) =>
    useQuery(storageQueries.bootOrder(uuid))
