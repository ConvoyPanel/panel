import type {
    ServerPreset,
    ServerPresetSettings,
} from '@/types/server-preset.ts'

/**
 * The API omits the settings an admin left blank, so every key is filled in
 * here as an explicit null. Consumers can then ask "did this preset say
 * anything about memory?" without telling `undefined` and `null` apart.
 */
const rawDataToSettings = (raw: any): ServerPresetSettings => ({
    nodeId: raw?.nodeId ?? null,
    storageId: raw?.storageId ?? null,
    cpu: raw?.cpu ?? null,
    memory: raw?.memory ?? null,
    disk: raw?.disk ?? null,
    bandwidth: raw?.bandwidth ?? null,
    speedLimit: raw?.speedLimit ?? null,
    backupCount: raw?.backupCount ?? null,
    backupSize: raw?.backupSize ?? null,
    // A nested data collection arrives wrapped (`{ data: [...] }`), the same
    // way a server's node does — see `rawDataToServer`. Reading the wrapper
    // only was what made a preset carrying extra disks fail to load at all.
    disks:
        (raw?.disks?.data ?? raw?.disks)?.map((disk: any) => ({
            storageId: disk.storageId,
            size: disk.size,
        })) ?? null,
    networkInterfaceId: raw?.networkInterfaceId ?? null,
    vlanTag: raw?.vlanTag ?? null,
    addressesIpv4Count: raw?.addressesIpv4Count ?? null,
    addressesIpv6Count: raw?.addressesIpv6Count ?? null,
    deferredOsSelection: raw?.deferredOsSelection ?? null,
    shouldCreateVm: raw?.shouldCreateVm ?? null,
    templateGroupUuid: raw?.templateGroupUuid ?? null,
    templateUuid: raw?.templateUuid ?? null,
    startOnCompletion: raw?.startOnCompletion ?? null,
})

export const rawDataToServerPreset = (raw: any): ServerPreset => ({
    uuid: raw.uuid,
    name: raw.name,
    description: raw.description ?? null,
    settings: rawDataToSettings(raw.settings?.data ?? raw.settings),
    createdAt: new Date(raw.createdAt),
    updatedAt: new Date(raw.updatedAt),
})
