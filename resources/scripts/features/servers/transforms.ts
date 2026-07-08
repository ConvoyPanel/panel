import { ServerResources } from '@/types/server'
import { Backup, BackupErrorCode } from '@/features/servers/types.ts'

export const rawDataToServerResources = (data: any): ServerResources => ({
    // ResourceController emits snake_case (it returns a raw array, not a laravel-data DTO).
    usedBytes: data.used_bytes,
    totalBytes: data.total_bytes,
})

export const rawDataToBackup = (data: any): Backup => ({
    id: data.id,
    uuid: data.uuid,
    serverId: data.serverId,
    storageId: data.storageId,
    name: data.name,
    description: data.description,
    isLocked: data.isLocked,
    errorCode: (data.errorCode as BackupErrorCode) ?? null,
    errorMessage: data.errorMessage ?? null,
    fileName: data.fileName,
    size: data.size,
    completedAt: data.completedAt ? new Date(data.completedAt) : null,
    createdAt: new Date(data.createdAt),
})
