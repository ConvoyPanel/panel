import {
    backupQueries,
    quotaBlockedReason,
    useBackupQuota,
} from '@/features/servers/backups/api.ts'
import BackupCard from '@/features/servers/components/client/Backups/BackupCard.tsx'
import CreateBackupModal from '@/features/servers/components/client/Backups/CreateBackupModal.tsx'
import type { PaginatedBackups } from '@/features/servers/types.ts'
import usePagination from '@/hooks/use-pagination.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { IconCopy } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { useParams } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'

const BackupView = () => {
    const { page, setPage } = usePagination()
    const { serverUuid } = useParams({ strict: false }) as {
        serverUuid: string
    }
    const { data, isLoading, isError, refetch } = useQuery(
        backupQueries.list(serverUuid, { page })
    )
    const mutate = useQueryMutator<PaginatedBackups>(
        backupQueries.list(serverUuid, { page }).queryKey
    )
    const quota = useBackupQuota()

    if (isLoading) {
        return <Skeleton className={'h-96 w-full'} />
    }

    if (isError && !data) {
        return (
            <Card className={'py-6'}>
                <CollectionErrorState onRetry={refetch} />
            </Card>
        )
    }

    if (!data || data?.items.length === 0) {
        // An empty list means three different things, and the reason a server
        // can't take a backup is exactly what an empty screen has to say. Only
        // the last of these is an invitation, so only the last gets the action.
        const state = quota?.isUnavailable
            ? {
                  title: 'Backups are not enabled',
                  description:
                      'This server has no backup slots allocated to it. Your administrator can add them.',
              }
            : quota?.hasNoStorage
              ? {
                    title: 'Backups are unavailable',
                    description:
                        'The node this server runs on has no storage configured for backups. Your administrator will need to add one.',
                }
              : {
                    title: 'Backups',
                    description:
                        'Backups ensure the safety and availability of your server data by creating copies that can be easily restored in case of data loss or system failures.',
                    canCreate: true,
                }

        return (
            <Card className={'py-6'}>
                <SimpleEmptyState
                    icon={IconCopy}
                    title={state.title}
                    description={state.description}
                    action={
                        // A disabled button implies a door that isn't there, so
                        // the states nothing can be done about carry no action.
                        state.canCreate && (
                            <CreateBackupModal
                                serverUuid={serverUuid}
                                mutate={mutate}
                                blockedReason={quotaBlockedReason(quota)}
                            />
                        )
                    }
                />
            </Card>
        )
    }

    return (
        <LengthAwarePaginator page={page} data={data} onPageChange={setPage}>
            {({ items }) => (
                <ItemGroup className={'gap-3'}>
                    {items.map(backup => (
                        <BackupCard backup={backup} key={backup.uuid} />
                    ))}
                </ItemGroup>
            )}
        </LengthAwarePaginator>
    )
}

export default BackupView
