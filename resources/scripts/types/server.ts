import { PaginatedResult } from '@/utils/http.ts'

export type ServerLifecycleStatus =
    | 'installing'
    | 'install_failed'
    | 'suspended'
    | 'restoring_backup'
    | 'restoring_snapshot'
    | 'deleting'
    | 'deletion_failed'
    | null

export type ServerPowerState = 'running' | 'stopped' | 'suspended'

export interface Server {
    id: number
    uuid: string
    uuidShort: string
    userId: number
    nodeId: number
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
