import usePagination from '@/hooks/use-pagination.ts'
import { useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'
import { IconCopy } from '@tabler/icons-react'

import { backupQueries } from '@/features/servers/backups/api.ts'

import BackupCard from '@/features/servers/components/client/Backups/BackupCard.tsx'

import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'


const BackupView = () => {
    const { page, setPage } = usePagination()
    const { serverUuid } = useParams({ strict: false }) as { serverUuid: string }
    const { data, isLoading } = useQuery(backupQueries.list(serverUuid, { page }))

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
