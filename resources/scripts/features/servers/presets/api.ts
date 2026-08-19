import type { ServerPreset, ServerPresetSettings } from '@/types/server-preset'
import ServerPresetController from '@/wayfinder/actions/App/Http/Controllers/Admin/ServerPresetController'
import { queryOptions, useQuery } from '@tanstack/react-query'
import { z } from 'zod'

import { type DataResponse, apiFetch } from '@/lib/api'
import { rawDataToServerPreset } from '@/lib/transformers/server-preset.ts'

/**
 * A preset's own details. The settings it carries are not edited here — they
 * are captured from the server-create form, which is the only place that knows
 * what a valid build looks like.
 */
export const serverPresetSchema = z.object({
    name: z.string().min(1, 'Name is required.').max(191),
    description: z.preprocess(
        value => (value === '' ? null : value),
        z.string().max(191).nullable()
    ),
})

// ServerPresetController is served under both the panel (`/api/admin`) and
// Application (`/api/application`) prefixes, so Wayfinder emits URI-keyed
// dictionaries — reference the admin route explicitly.
const indexRoute = ServerPresetController.index['/api/admin/server-presets']
const storeRoute = ServerPresetController.store['/api/admin/server-presets']
const updateRoute =
    ServerPresetController.update['/api/admin/server-presets/{server_preset}']
const destroyRoute =
    ServerPresetController.destroy['/api/admin/server-presets/{server_preset}']

export const getServerPresets = async (): Promise<ServerPreset[]> => {
    const res = await apiFetch<DataResponse<unknown[]>>(indexRoute())

    return res.data.map(rawDataToServerPreset)
}

export const serverPresetQueries = {
    all: () => ['admin', 'server-presets'] as const,
    list: () =>
        queryOptions({
            queryKey: [...serverPresetQueries.all(), 'list'] as const,
            queryFn: getServerPresets,
        }),
}

export const useServerPresets = () => useQuery(serverPresetQueries.list())

/**
 * The wire shape of a preset's settings: snake_case, and with the keys the
 * admin left blank omitted rather than sent as null — the API stores exactly
 * what it is given, and a preset is meant to be partial.
 */
const settingsPayload = (settings: Partial<ServerPresetSettings>) => {
    const payload: Record<string, unknown> = {
        node_id: settings.nodeId,
        storage_id: settings.storageId,
        cpu: settings.cpu,
        memory: settings.memory,
        disk: settings.disk,
        bandwidth: settings.bandwidth,
        speed_limit: settings.speedLimit,
        backup_count: settings.backupCount,
        backup_size: settings.backupSize,
        disks: settings.disks?.map(disk => ({
            storage_id: disk.storageId,
            size: disk.size,
        })),
        network_interface_id: settings.networkInterfaceId,
        vlan_tag: settings.vlanTag,
        addresses_ipv4_count: settings.addressesIpv4Count,
        addresses_ipv6_count: settings.addressesIpv6Count,
        deferred_os_selection: settings.deferredOsSelection,
        should_create_vm: settings.shouldCreateVm,
        template_group_uuid: settings.templateGroupUuid,
        template_uuid: settings.templateUuid,
        start_on_completion: settings.startOnCompletion,
    }

    return Object.fromEntries(
        Object.entries(payload).filter(([, value]) => value != null)
    )
}

export const createServerPreset = async (
    details: z.infer<typeof serverPresetSchema>,
    settings: Partial<ServerPresetSettings>
): Promise<ServerPreset> =>
    rawDataToServerPreset(
        (
            await apiFetch<DataResponse<unknown>>(storeRoute(), {
                body: { ...details, settings: settingsPayload(settings) },
            })
        ).data
    )

export const updateServerPreset = async (
    uuid: string,
    details: z.infer<typeof serverPresetSchema>,
    settings: Partial<ServerPresetSettings>
): Promise<ServerPreset> =>
    rawDataToServerPreset(
        (
            await apiFetch<DataResponse<unknown>>(updateRoute(uuid), {
                body: { ...details, settings: settingsPayload(settings) },
            })
        ).data
    )

export const deleteServerPreset = async (uuid: string): Promise<void> => {
    await apiFetch(destroyRoute(uuid))
}
