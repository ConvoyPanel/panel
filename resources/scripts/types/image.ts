/** One disk belonging to an image version. */
export interface ImageDisk {
    /** The `qm` slot this disk becomes, e.g. `scsi0` or `efidisk0`. */
    slot: string
    role: ImageDiskRole
    /** A public origin, or null when the panel is serving the file itself. */
    url: string | null
    /** Path on the panel's images filesystem, or null when `url` is set. */
    path: string | null
    sha256: string
    /** Bytes on the wire — what a node has to transfer. */
    size: number
    /** Provisioned size once imported. The smallest plan disk that fits. */
    virtualSize: number
    format: string
}

export enum ImageDiskRole {
    SYSTEM = 'system',
    EFIVARS = 'efivars',
}

/** One build of a definition. Replaced, never edited. */
export interface ImageVersion {
    uuid: string
    version: string
    disks: ImageDisk[]
    size: number
    minimumDisk: number
    isActive: boolean
}

/**
 * A type of image — "Ubuntu 24.04" — and what is needed to boot one.
 *
 * `hardware` is only what an admin overrode; `effectiveHardware` is that
 * resolved over the OS default. The form needs both so it can show a field as
 * inherited rather than blank, and so clearing one means "inherit again".
 */
export interface ImageDefinition {
    uuid: string
    imageGroupUuid: string
    name: string
    description: string | null
    isAdminOnly: boolean
    ostype: string
    hardware: Record<string, unknown>
    effectiveHardware: Record<string, unknown>
    minimumCores: number | null
    minimumMemory: number | null
    latestVersion: ImageVersion | null
    versions?: ImageVersion[]
}

/** How the OS picker is organised. Carries no bytes and no hardware. */
export interface ImageGroup {
    uuid: string
    name: string
    description: string | null
    icon: ImageIcon | null
    isAdminOnly: boolean
    definitions?: ImageDefinition[]
}

export enum ImageIcon {
    UBUNTU = 'ubuntu',
    DEBIAN = 'debian',
    CENTOS = 'centos',
    FEDORA = 'fedora',
    ROCKY_LINUX = 'rocky_linux',
    ALMALINUX = 'almalinux',
    WINDOWS = 'windows',
    ALPINE_LINUX = 'alpine_linux',
    ARCH_LINUX = 'arch_linux',
}

/**
 * One parameter as Proxmox itself describes it.
 *
 * Read off a node rather than restated here, so the hardware form's selects,
 * patterns and help text are the running Proxmox's own and cannot drift from
 * what it will accept.
 */
export interface PveParameter {
    type?: string
    enum?: string[]
    default?: unknown
    description?: string
    optional?: number
}

export interface HardwareSchema {
    /** Whose rules these are; null when the panel's bundled copy was used. */
    nodeId: number | null
    parameters: Record<string, PveParameter>
    /** Panel-owned keys that name a slot rather than carrying a PVE value. */
    metaKeys: string[]
    defaults: Record<string, Record<string, unknown>>
}
