import { NodeStorage, Storage, StorageProxmox } from '@/types/storage'

export const rawDataToStorage = (raw: any): Storage => ({
    id: raw.id,
    displayName: raw.displayName,
    description: raw.description,
    name: raw.name,
    size: raw.size,
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
    }
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
