import { PaginatedResult } from '@/utils/http'

export interface Storage {
    id: number
    displayName: string | null
    description: string | null
    name: string
    size: number
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
