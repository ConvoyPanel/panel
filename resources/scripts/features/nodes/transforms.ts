import { NodeStorage, Storage, StorageProxmox } from '@/features/nodes/types'

export const rawDataToStorage = (raw: any): Storage => ({
    id: raw.id,
    displayName: raw.displayName,
    description: raw.description,
    name: raw.name,
    size: raw.size,
    reservedBytes: raw.reservedBytes ?? null,
    isShareable: raw.isShareable,
    storesKvm: raw.storesKvm,
    storesLxc: raw.storesLxc,
    storesLxcTemplates: raw.storesLxcTemplates,
    storesBackups: raw.storesBackups,
    storesIso: raw.storesIso,
    storesSnippets: raw.storesSnippets,
    usages: {
        server: raw.serverUsage ?? raw.usages?.server,
        backup: raw.backupUsage ?? raw.usages?.backup,
        iso: raw.isoUsage ?? raw.usages?.iso,
    },
    committedByConvoy:
        raw.committedByConvoy ??
        (raw.serverUsage ?? 0) + (raw.backupUsage ?? 0) + (raw.isoUsage ?? 0),
    pveType: raw.pveType ?? null,
    pveShared: raw.pveShared ?? null,
    pveContent: raw.pveContent ?? null,
    isThin: raw.isThin ?? false,
    sharedWith: raw.sharedWith ?? [],
    online: raw.online ?? false,
    capacitySource: raw.capacitySource ?? 'unknown',
    observedAt: raw.observedAt ?? null,
    physicalTotal: raw.physicalTotal ?? null,
    physicalUsed: raw.physicalUsed ?? null,
    physicalFree: raw.physicalFree ?? null,
    untracked: raw.untracked ?? null,
    freeForConvoy: raw.freeForConvoy ?? null,
})

export const rawDataToNodeStorage = (raw: any): NodeStorage => ({
    ...rawDataToStorage(raw),
    backupOrder: raw.backupOrder,
})

export const rawDataToStorageProxmox = (raw: any): StorageProxmox => ({
    name: raw.name,
    used: raw.used,
    free: raw.free,
    total: raw.total,
    enabled: raw.enabled,
    online: raw.online,
    isShareable: raw.isShareable,
    storesKvm: raw.storesKvm,
    storesLxc: raw.storesLxc,
    storesLxcTemplates: raw.storesLxcTemplates,
    storesBackups: raw.storesBackups,
    storesIso: raw.storesIso,
    storesSnippets: raw.storesSnippets,
})
