import { Node } from '@/types/node'
import { PaginatedResult } from '@/utils/http.ts'

export enum ServerLifecycleStatus {
    Ready = 'ready',
    DeferredOsSelection = 'deferred_os_selection',
    Installing = 'installing',
    InstallFailed = 'install_failed',
    Suspended = 'suspended',
    RestoringBackup = 'restoring_backup',
    RestoringSnapshot = 'restoring_snapshot',
    Deleting = 'deleting',
    DeletionFailed = 'deletion_failed',
}

export interface Server {
    id: number
    uuid: string
    uuidShort: string
    userId: number
    nodeId: number
    node?: Node
    vmid: number
    hostname: string
    name: string
    description: string | null
    status: ServerLifecycleStatus
    cpu: number
    memory: number
    disk: number
    snapshot: {
        countLimit: number
        sizeLimit: number
    }
    backup: {
        countLimit: number
        sizeLimit: number
    }
    bandwidth: {
        usage: number
        limit: number
    }
    createdAt: Date
}

export type PaginatedServers = PaginatedResult<Server>

export type ServerState = 'running' | 'stopped'

export interface ServerStateData {
    state: ServerState
    cpuUsed: number
    memoryTotal: number
    memoryUsed: number
    uptime: number
}

export interface ServerTimepointData {
    cpuUsed: number
    memoryUsed: number
    network: {
        in: number
        out: number
    }
    disk: {
        write: number
        read: number
    }
    timestamp: Date
}
