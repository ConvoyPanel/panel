import { Template } from '@/types/template'
import { PaginatedResult } from '@/utils/http.ts'

export enum DeploymentStatus {
    Pending = 'pending',
    Running = 'running',
    Completed = 'completed',
    Failed = 'failed',
}

export enum DeploymentType {
    Install = 'install',
    Reinstall = 'reinstall',
    Delete = 'delete',
    Import = 'import',
}

export interface DeploymentStep {
    id: number
    name: string
    status: DeploymentStatus
    progressCurrent: number
    progressTotal: number
    startedAt: Date | null
    completedAt: Date | null
    errorCode: string | null
    errorMessage: string | null
}

export interface Deployment {
    id: number
    serverId: number
    templateId: number | null
    status: DeploymentStatus
    type: DeploymentType
    startOnCompletion: boolean
    requestedAt: Date
    completedAt: Date | null
    template?: Template
    steps: DeploymentStep[]
}

export interface Backup {
    id: number
    uuid: string
    serverId: number
    storageId: number
    name: string
    description: string | null
    isLocked: boolean
    errors: BackupError | null
    fileName: string
    size: number
    completedAt: Date | null
    createdAt: Date
}

export enum BackupError {
    StorageExceeded,
}

export type PaginatedBackups = PaginatedResult<Backup> & { backupCount: number }
