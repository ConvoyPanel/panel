import { PaginatedResult } from '@/utils/http'

export interface Storage {
    id: number
    displayName: string | null
    description: string | null
    name: string
    size: number
    reservedBytes: number | null
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
    // What Proxmox reports this storage is, recorded by the node poll. Null
    // until a node has reported it at least once.
    pveType: string | null
    pveShared: boolean | null
    pveContent: string | null
    /**
     * Whether committed may legitimately exceed physical usage — thin backends
     * and PBS. A large gap there is ordinary, not something to warn about.
     */
    isThin: boolean
    /**
     * The other nodes reaching this same storage, named. Empty when only this
     * node has it.
     */
    sharedWith: { id: number; name: string }[]
    // Whether the physical figures below came from a live call this request.
    online: boolean
    /**
     * Where the physical figures came from. `recorded` means the node was
     * unreachable and these are the last ones the poll wrote, which is why
     * `online` being false no longer implies they are missing.
     */
    capacitySource: 'live' | 'recorded' | 'unknown'
    // When the physical figures were observed. Null when never.
    observedAt: string | null
    physicalTotal: number | null
    physicalUsed: number | null
    physicalFree: number | null
    // physicalUsed − committedByConvoy. Null when unknown, and null on thin or
    // deduplicating backends where the subtraction is not valid.
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
