import { NodeStorage, Storage, StorageProxmox } from '@/types/storage'

export const rawDataToStorage = (raw: any): Storage => ({
    id: raw.id,
    displayName: raw.display_name,
    description: raw.description,
    name: raw.name,
    size: raw.size,
    isShareable: raw.is_shareable,
    storesKvm: raw.stores_kvm,
    storesLxc: raw.stores_lxc,
    storesLxcTemplates: raw.stores_lxc_templates,
    storesBackups: raw.stores_backups,
    storesIso: raw.stores_iso,
    storesSnippets: raw.stores_snippets,
    usages: {
        server: raw.server_usage,
        backup: raw.backup_usage,
        iso: raw.iso_usage,
    }
})

export const rawDataToNodeStorage = (raw: any): NodeStorage => ({
    ...rawDataToStorage(raw),
    backupOrder: raw.backup_order,
})

export const rawDataToStorageProxmox = (raw: any): StorageProxmox => ({
    name: raw.name,
    used: raw.used,
    free: raw.free,
    total: raw.total,
    enabled: raw.enabled,
    online: raw.online,
    isShareable: raw.is_shareable,
    storesKvm: raw.stores_kvm,
    storesLxc: raw.stores_lxc,
    storesLxcTemplates: raw.stores_lxc_templates,
    storesBackups: raw.stores_backups,
    storesIso: raw.stores_iso,
    storesSnippets: raw.stores_snippets,
})
