import usePagination from '@/hooks/use-pagination.ts'
import { IconCopy } from '@tabler/icons-react'

import useBackupsSWR from '@/api/servers/backups/use-backups-swr.ts'

import BackupCard from '@/components/interfaces/Client/Server/Backups/BackupCard.tsx'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'


const BackupView = () => {
    const { page, setPage } = usePagination()
    const { data, isLoading } = useBackupsSWR(undefined, { page })

    if (isLoading) {
        return <Skeleton className={'h-96 w-full'} />
    }

    if (!data || data?.items.length === 0) {
        return (
            <Card className={'py-6'}>
                <SimpleEmptyState
                    icon={IconCopy}
                    title={'Backups'}
                    description={
                        'Backups ensure the safety and availability of your server data by creating copies that can be easily restored in case of data loss or system failures.'
                    }
                    action={<Button>Create Backup</Button>}
                />
            </Card>
        )
    }

    return (
        <LengthAwarePaginator page={page} data={data} onPageChange={setPage}>
            {({ items }) =>
                items.map(backup => (
                    <BackupCard backup={backup} key={backup.uuid} />
                ))
            }
        </LengthAwarePaginator>
    )
}

export default BackupView
