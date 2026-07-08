import { queryOptions, useQuery } from '@tanstack/react-query'

import { apiFetch, type DataResponse } from '@/lib/api'
import SettingsController from '@/wayfinder/actions/App/Http/Controllers/Client/Servers/SettingsController'

// Live filesystem usage comes from the shared `useServerResources` hook (detail/api.ts) — no
// duplicate reader here.

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
    bootOrder: (uuid: string) =>
        queryOptions({
            queryKey: ['servers', uuid, 'boot-order'] as const,
            queryFn: () => getBootOrder(uuid),
        }),
}

export const useBootOrder = (uuid: string) =>
    useQuery(storageQueries.bootOrder(uuid))
