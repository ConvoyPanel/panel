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

export enum ProgressMode {
    Determinate = 'determinate',
    Indeterminate = 'indeterminate',
}

export interface DeploymentStep {
    id: number
    name: string
    status: DeploymentStatus
    progressMode: ProgressMode
    sequence: number
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
    errorCode: BackupErrorCode | null
    // Raw failure detail from Proxmox; only present for admins (may leak node
    // internals), so treat it as optional even on a failed backup.
    errorMessage: string | null
    fileName: string
    size: number
    completedAt: Date | null
    createdAt: Date
}

// Mirrors App\Enums\Server\Backup\BackupErrorCode. A stable, client-safe code
// the backend classifies raw failures into; unknown reasons fall through to Other.
export enum BackupErrorCode {
    StorageExceeded = 'storage_exceeded',
    Timeout = 'timeout',
    Other = 'other',
}

export type PaginatedBackups = PaginatedResult<Backup> & {
    // Quota figures spanning every non-failed backup, not just the current page.
    backupCount: number
    backupSize: number
}
