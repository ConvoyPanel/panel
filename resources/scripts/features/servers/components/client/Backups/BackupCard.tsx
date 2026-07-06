import { Backup } from '@/types/backup.ts'

import { Card } from '@/components/ui/Card'

interface Props {
    backup: Backup
}

const BackupCard = ({ backup }: Props) => {
    return (
        <Card className={'flex items-center py-2.5 px-5'}>
            <p className={'font-semibold'}>{backup.name}</p>
        </Card>
    )
}

export default BackupCard
