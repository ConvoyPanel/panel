import usePagination from '@/hooks/use-pagination.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'
import { IconCopy } from '@tabler/icons-react'

import { backupQueries } from '@/features/servers/backups/api.ts'
import type { PaginatedBackups } from '@/features/servers/types.ts'

import BackupCard from '@/features/servers/components/client/Backups/BackupCard.tsx'
import CreateBackupModal from '@/features/servers/components/client/Backups/CreateBackupModal.tsx'

import { Card } from '@/components/ui/Card'
import { SimpleEmptyState } from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'


const BackupView = () => {
    const { page, setPage } = usePagination()
    const { serverUuid } = useParams({ strict: false }) as { serverUuid: string }
    const { data, isLoading } = useQuery(backupQueries.list(serverUuid, { page }))
    const mutate = useQueryMutator<PaginatedBackups>(
        backupQueries.list(serverUuid, { page }).queryKey
    )

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
                    action={
                        <CreateBackupModal
                            serverUuid={serverUuid}
                            mutate={mutate}
                        />
                    }
                />
            </Card>
        )
    }

    return (
        <LengthAwarePaginator page={page} data={data} onPageChange={setPage}>
            {({ items }) =>
                <ItemGroup className={'gap-3'}>
                    {items.map(backup => (
                        <BackupCard backup={backup} key={backup.uuid} />
                    ))}
                </ItemGroup>
            }
        </LengthAwarePaginator>
    )
}

export default BackupView
