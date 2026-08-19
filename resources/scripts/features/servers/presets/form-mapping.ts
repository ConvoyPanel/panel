import type { ServerPresetSettings } from '@/types/server-preset'
import type { UseFormSetValue } from 'react-hook-form'

/**
 * The create form's shape as far as presets are concerned. Ids are strings
 * because that is what the pickers bind to; `templateGroupId` exists only to
 * filter the template list and is not part of the create payload.
 */
export interface PresetFormValues {
    nodeId?: string
    storageId?: string
    cpu?: number | string
    memory?: number | string
    disk?: number | string
    bandwidth?: number | string
    speedLimit?: number | string
    disks?: { storageId?: string; size?: number | string }[]
    backupCount?: number | string
    backupSize?: number | string
    networkInterfaceId?: string
    vlanTag?: number | string | null
    addressesIpv4Count?: number | string
    addressesIpv6Count?: number | string
    deferredOsSelection?: boolean
    shouldCreateVm?: boolean
    templateGroupId?: string
    templateUuid?: string
    startOnCompletion?: boolean
}

/** `''`, null and undefined all mean "the operator left this alone". */
const numberOrNull = (value: unknown): number | null => {
    if (value === '' || value == null) return null

    const parsed = Number(value)

    return Number.isFinite(parsed) ? parsed : null
}

const stringOrNull = (value: unknown): string | null =>
    typeof value === 'string' && value !== '' ? value : null

/**
 * Read a preset out of the form as it currently stands.
 *
 * Identity — name, hostname, VMID, owner — and the account password are
 * deliberately not read: the first four are what makes a server that server,
 * and a password does not belong in a record meant to be reused.
 */
export const presetSettingsFromForm = (
    values: PresetFormValues
): Partial<ServerPresetSettings> => {
    const nodeId = numberOrNull(values.nodeId)

    // Storage, bridge and extra disks are ids that only mean something on the
    // node they were picked on, so without a node they are dropped rather than
    // saved as numbers pointing at another node's hardware.
    const disks =
        nodeId == null
            ? null
            : (values.disks
                  ?.map(disk => ({
                      storageId: numberOrNull(disk.storageId),
                      size: numberOrNull(disk.size),
                  }))
                  .filter(
                      (disk): disk is { storageId: number; size: number } =>
                          disk.storageId != null && disk.size != null
                  ) ?? [])

    return {
        nodeId,
        storageId: nodeId == null ? null : numberOrNull(values.storageId),
        cpu: numberOrNull(values.cpu),
        memory: numberOrNull(values.memory),
        disk: numberOrNull(values.disk),
        bandwidth: numberOrNull(values.bandwidth),
        speedLimit: numberOrNull(values.speedLimit),
        backupCount: numberOrNull(values.backupCount),
        backupSize: numberOrNull(values.backupSize),
        disks: disks && disks.length > 0 ? disks : null,
        networkInterfaceId:
            nodeId == null ? null : numberOrNull(values.networkInterfaceId),
        vlanTag: nodeId == null ? null : numberOrNull(values.vlanTag),
        addressesIpv4Count: numberOrNull(values.addressesIpv4Count),
        addressesIpv6Count: numberOrNull(values.addressesIpv6Count),
        deferredOsSelection: values.deferredOsSelection ?? null,
        shouldCreateVm: values.shouldCreateVm ?? null,
        templateGroupUuid: stringOrNull(values.templateGroupId),
        templateUuid: stringOrNull(values.templateUuid),
        startOnCompletion: values.startOnCompletion ?? null,
    }
}

/**
 * Write a preset back into the form.
 *
 * Only the settings the preset actually carries are written — a preset that
 * says nothing about backups leaves whatever is in the backup fields alone,
 * which is what lets one be applied over a half-filled form.
 */
export const applyPresetSettings = (
    settings: ServerPresetSettings,
    setValue: UseFormSetValue<any>
) => {
    const set = (name: string, value: unknown) => {
        if (value == null) return

        // `shouldDirty` so the fields a preset filled are submitted and read
        // back as the operator's own answers; `shouldValidate` so an
        // impossible combination surfaces now rather than at submit.
        setValue(name, value, { shouldDirty: true, shouldValidate: true })
    }

    // The node goes first: the storage, bridge and disk pickers all list
    // against it, so setting it last would leave them briefly querying the
    // node that was selected before.
    set('nodeId', settings.nodeId?.toString())
    set('storageId', settings.storageId?.toString())

    set('cpu', settings.cpu)
    set('memory', settings.memory)
    set('disk', settings.disk)
    set('bandwidth', settings.bandwidth)
    set('speedLimit', settings.speedLimit)
    set('backupCount', settings.backupCount)
    set('backupSize', settings.backupSize)

    set(
        'disks',
        settings.disks?.map(disk => ({
            storageId: disk.storageId.toString(),
            size: disk.size,
        }))
    )

    set('networkInterfaceId', settings.networkInterfaceId?.toString())
    set('vlanTag', settings.vlanTag?.toString())
    set('addressesIpv4Count', settings.addressesIpv4Count)
    set('addressesIpv6Count', settings.addressesIpv6Count)

    set('deferredOsSelection', settings.deferredOsSelection)
    set('shouldCreateVm', settings.shouldCreateVm)
    set('templateGroupId', settings.templateGroupUuid)
    set('templateUuid', settings.templateUuid)
    set('startOnCompletion', settings.startOnCompletion)
}

/**
 * A short human summary of what a preset will fill in, for the picker's second
 * line. Deliberately the resource shape rather than a field count — "2 vCPU ·
 * 2 GiB · 20 GiB" is what an admin recognises a build by.
 */
export const describePresetSettings = (
    settings: ServerPresetSettings
): string => {
    const parts: string[] = []

    if (settings.cpu != null) parts.push(`${settings.cpu} vCPU`)
    if (settings.memory != null)
        parts.push(`${Math.round((settings.memory / 1024) * 10) / 10} GiB RAM`)
    if (settings.disk != null)
        parts.push(`${Math.round((settings.disk / 1024) * 10) / 10} GiB disk`)
    if (settings.disks?.length)
        parts.push(
            `${settings.disks.length} extra ${settings.disks.length === 1 ? 'disk' : 'disks'}`
        )

    return parts.length > 0 ? parts.join(' · ') : 'No resource limits saved'
}
