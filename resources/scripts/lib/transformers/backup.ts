import { Backup, BackupError } from '@/types/backup.ts'

export const rawDataToBackup = (data: any): Backup => ({
    id: data.id,
    uuid: data.uuid,
    serverId: data.serverId,
    storageId: data.storageId,
    name: data.name,
    description: data.description,
    isLocked: data.isLocked,
    errors: data.errors
        ? BackupError[data.errors as keyof typeof BackupError]
        : null,
    fileName: data.fileName,
    size: data.size,
    completedAt: data.completedAt ? new Date(data.completedAt) : null,
    createdAt: new Date(data.createdAt),
})
