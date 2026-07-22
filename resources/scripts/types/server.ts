import { Node } from '@/types/node'
import { PaginatedResult } from '@/utils/http.ts'

export enum ServerLifecycleStatus {
    Ready = 'ready',
    DeferredOsSelection = 'deferred_os_selection',
    Installing = 'installing',
    InstallFailed = 'install_failed',
    Suspended = 'suspended',
    RestoringBackup = 'restoring_backup',
    Deleting = 'deleting',
    DeletionFailed = 'deletion_failed',
}

export interface Server {
    id: number
    uuid: string
    uuidShort: string
    userId: number
    nodeId: number
    networkInterfaceId: number | null
    node?: Node
    vmid: number
    hostname: string
    name: string
    description: string | null
    status: ServerLifecycleStatus
    powerState: App.Enums.Server.State | null
    cpu: number
    memory: number
    disk: number
    backup: {
        countLimit: number
        sizeLimit: number
    }
    bandwidth: {
        usage: number
        limit: number
        speedLimit: number | null
        overagePenalty: App.Data.Server.OveragePenaltyData | null
    }
    vlanTag: number | null
    createdAt: Date
}

export type PaginatedServers = PaginatedResult<Server>

export type ServerState = 'running' | 'stopped'

export interface PendingPowerAction {
    command: 'start' | 'restart' | 'shutdown' | 'kill' | 'reset' | 'resume' | 'suspend'
    requestedAt: string
}

export interface ServerStateData {
    state: ServerState
    cpuUsed: number
    memoryTotal: number
    memoryUsed: number
    uptime: number
    pendingPowerAction: PendingPowerAction | null
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

export interface ServerResources {
    usedBytes: number
    totalBytes: number
}
