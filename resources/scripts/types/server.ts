import { Node } from '@/types/node'
import { PaginatedResult } from '@/utils/http.ts'

/**
 * Where Convoy has a server in its provisioning lifecycle.
 *
 * A runtime mirror of `App.Enums.Server.ServerLifecycle`, which the generator emits as a
 * type-only union — this exists so the value can be used in label maps and comparisons.
 *
 * Suspension is deliberately absent: it is an independent axis carried by `suspendedAt`,
 * and a suspended server still reports whatever lifecycle it was in.
 */
export enum ServerLifecycle {
    Ready = 'ready',
    DeferredOsSelection = 'deferred_os_selection',
    Installing = 'installing',
    InstallFailed = 'install_failed',
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
    /** Convoy's provisioning stage. Says nothing about suspension or power. */
    lifecycle: ServerLifecycle
    /** ISO timestamp of an administrative suspension, or null if not suspended. */
    suspendedAt: string | null
    /**
     * When the placement reconciler flagged this server for an operator, and
     * why. Both are null for non-admin viewers, not just unflagged servers.
     */
    flaggedAt: string | null
    flagReason: string | null
    /** The guest's live power state per the last poll, or null for "we cannot say". */
    powerState: App.Enums.Server.PowerState | null
    cpu: number
    memory: number
    disk: number
    backup: {
        countLimit: number
        sizeLimit: number
        /** False when the node has nowhere to put a backup, so none can run. */
        hasStorage: boolean
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

/**
 * The subset of `PowerState` the live status endpoint reports — Proxmox only ever answers
 * running or stopped there; the synthetic transition states are Convoy's own.
 */
export type ObservedPowerState = 'running' | 'stopped'

export interface PendingPowerAction {
    command:
        | 'start'
        | 'restart'
        | 'shutdown'
        | 'kill'
        | 'reset'
        | 'resume'
        | 'suspend'
    requestedAt: string
}

export interface PowerActionResult {
    command: PendingPowerAction['command']
    // Mirrors the pending action's requestedAt, so a result can be matched to
    // the specific action the UI was showing as in progress.
    requestedAt: string
    ok: boolean
    // Proxmox's raw task exit string on failure; "OK"/"WARNINGS" on success.
    exitStatus: string | null
}

export interface ServerStateData {
    powerState: ObservedPowerState
    cpuUsed: number
    memoryTotal: number
    memoryUsed: number
    uptime: number
    pendingPowerAction: PendingPowerAction | null
    lastPowerAction: PowerActionResult | null
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
