import { PaginatedResult } from '@/utils/http'

export interface Storage {
    id: number
    displayName: string | null
    description: string | null
    name: string
    size: number
    reservedBytes: number | null
    isShareable: boolean
    storesKvm: boolean
    storesLxc: boolean
    storesLxcTemplates: boolean
    storesBackups: boolean
    storesIso: boolean
    storesSnippets: boolean
    usages: {
        server: number
        backup: number
        iso: number
    }
    // Sum of the usages above — "Allocated by Convoy".
    committedByConvoy: number
    // Live Proxmox truth. `online` false ⇒ physical figures are null (offline).
    online: boolean
    physicalTotal: number | null
    physicalUsed: number | null
    physicalFree: number | null
    // physicalUsed − committedByConvoy: base-system + non-Convoy usage.
    untracked: number | null
    // physicalFree − reservedBytes: what a new disk may actually consume.
    freeForConvoy: number | null
}

export interface NodeStorage extends Storage {
    backupOrder: number | null
}

export interface StorageProxmox {
    name: string
    used: number
    free: number
    total: number
    enabled: boolean
    online: boolean
    isShareable: boolean
    storesKvm: boolean
    storesLxc: boolean
    storesLxcTemplates: boolean
    storesBackups: boolean
    storesIso: boolean
    storesSnippets: boolean
}

export type PaginatedStorages = PaginatedResult<Storage>
