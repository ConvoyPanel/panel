import {
    backupQueries,
    quotaBlockedReason,
    useBackupQuota,
} from '@/features/servers/backups/api.ts'
import BackupQuota from '@/features/servers/components/client/Backups/BackupQuota.tsx'
import BackupView from '@/features/servers/components/client/Backups/BackupView.tsx'
import CreateBackupModal from '@/features/servers/components/client/Backups/CreateBackupModal.tsx'
import type { PaginatedBackups } from '@/features/servers/types.ts'
import usePagination from '@/hooks/use-pagination.ts'
import useQueryMutator from '@/hooks/use-query-mutator.ts'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'

import Heading from '@/components/ui/Typography/Heading.tsx'

const ServerBackups = () => {
    const { serverUuid } = Route.useParams()
    const { page } = usePagination()
    // Same query key as BackupView and the quota, so this is a cache read.
    const { data } = useQuery(backupQueries.list(serverUuid, { page }))
    const mutate = useQueryMutator<PaginatedBackups>(
        backupQueries.list(serverUuid, { page }).queryKey
    )
    const quota = useBackupQuota()

    return (
        <>
            <div
                className={'flex flex-wrap items-center justify-between gap-2'}
            >
                <Heading>Backups</Heading>
                <div className={'flex gap-2'}>
                    <BackupQuota />
                    {/* An empty collection carries its own create action, so the
                        page-level one would be a duplicate. */}
                    {Boolean(data?.items.length) && !quota?.isUnavailable && (
                        <CreateBackupModal
                            serverUuid={serverUuid}
                            mutate={mutate}
                            blockedReason={quotaBlockedReason(quota)}
                        />
                    )}
                </div>
            </div>
            <BackupView />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/backups')({
    component: ServerBackups,
    // @ts-ignore
    meta: () => [{ title: 'Backups' }],
})
