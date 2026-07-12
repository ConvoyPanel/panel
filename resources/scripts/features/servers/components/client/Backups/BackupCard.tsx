import { Backup, BackupErrorCode } from '@/features/servers/types.ts'
import { IconAlertTriangle } from '@tabler/icons-react'

import {
    Item,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/Item'

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
        <Item variant={'muted'} size={'sm'}>
            <ItemContent className={'overflow-x-hidden'}>
                <ItemTitle className={'truncate'}>{backup.name}</ItemTitle>
                {isFailed && (
                    <ItemDescription className={'text-destructive'}>
                        <span className={'inline-flex items-center gap-1'}>
                            <IconAlertTriangle className={'h-3.5 w-3.5'} />
                            Failed
                        </span>{' '}
                        {BACKUP_ERROR_LABELS[backup.errorCode!]}
                        {backup.errorMessage ? `: ${backup.errorMessage}` : ''}
                    </ItemDescription>
                )}
            </ItemContent>
        </Item>
    )
}

export default BackupCard
