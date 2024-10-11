import { PaginatedResult } from '@/utils/http.ts'

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
    StorageExceeded = 'STORAGE_EXCEEDED',
}

export type PaginatedBackups = PaginatedResult<Backup> & { backupCount: number }
