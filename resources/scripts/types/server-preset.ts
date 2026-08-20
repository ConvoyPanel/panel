/**
 * The saved half of the admin server-create form.
 *
 * Every field is nullable, and that is the design: a preset answers the
 * questions an admin repeats and stays silent on the rest, so applying one
 * fills the fields it names and leaves the others alone.
 *
 * Units are the form's own — MiB for memory and disk, GiB per extra disk, MB/s
 * for the NIC cap — so applying a preset is a plain `setValue`, with the byte
 * conversions left where they already happen, at submit time.
 */
export interface ServerPresetSettings {
    /** Node-scoped ids below are only saved alongside the node they were picked on. */
    nodeId: number | null
    storageId: number | null
    cpu: number | null
    /** Mebibytes. */
    memory: number | null
    /** Mebibytes. */
    disk: number | null
    /** Mebibytes. */
    bandwidth: number | null
    /** Megabytes per second, per NIC. -1 leaves the NIC uncapped. */
    speedLimit: number | null
    /** -1 for unlimited. */
    backupCount: number | null
    /** Mebibytes; -1 for unlimited. */
    backupSize: number | null
    /** Extra data disks, sized in GiB. */
    disks: ServerPresetDisk[] | null
    networkInterfaceId: number | null
    vlanTag: number | null
    addressesIpv4Count: number | null
    addressesIpv6Count: number | null
    deferredOsSelection: boolean | null
    shouldCreateVm: boolean | null
    /**
     * The template's group. Saved alongside the template because the picker
     * lists by group — without it the form would show a chosen OS beside an
     * empty group.
     */
    templateGroupUuid: string | null
    templateUuid: string | null
    startOnCompletion: boolean | null
}

export interface ServerPresetDisk {
    storageId: number
    /** Gibibytes, as the create form asks for. */
    size: number
}

export interface ServerPreset {
    uuid: string
    name: string
    description: string | null
    settings: ServerPresetSettings
    createdAt: Date
    updatedAt: Date
}
