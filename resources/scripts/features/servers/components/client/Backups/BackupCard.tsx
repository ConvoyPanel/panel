import { Backup, BackupErrorCode } from '@/features/servers/types.ts'
import { IconAlertTriangle } from '@tabler/icons-react'

import { Card } from '@/components/ui/Card'

interface Props {
    backup: Backup
}

// Friendly labels for the client-safe error codes. The raw errorMessage (admin
// only) is appended when present for the extra detail.
const BACKUP_ERROR_LABELS: Record<BackupErrorCode, string> = {
    [BackupErrorCode.StorageExceeded]: 'Storage quota exceeded',
    [BackupErrorCode.Timeout]: 'Backup timed out',
    [BackupErrorCode.Other]: 'Backup failed',
}

const BackupCard = ({ backup }: Props) => {
    const isFailed = backup.errorCode !== null

    return (
        <Card className={'flex flex-col gap-1 py-2.5 px-5'}>
            <div className={'flex items-center gap-2'}>
                <p className={'font-semibold'}>{backup.name}</p>
                {isFailed && (
                    <span
                        className={
                            'inline-flex items-center gap-1 text-xs font-medium text-red-500'
                        }
                    >
                        <IconAlertTriangle className={'h-3.5 w-3.5'} />
                        Failed
                    </span>
                )}
            </div>
            {isFailed && (
                <p className={'text-xs text-red-500'}>
                    {BACKUP_ERROR_LABELS[backup.errorCode!]}
                    {backup.errorMessage ? `: ${backup.errorMessage}` : ''}
                </p>
            )}
        </Card>
    )
}

export default BackupCard
