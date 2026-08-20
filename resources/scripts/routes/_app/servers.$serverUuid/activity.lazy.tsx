import { serverAuditQueries } from '@/features/audit/api.ts'
import AuditFeed from '@/features/audit/components/AuditFeed.tsx'
import usePagination from '@/hooks/use-pagination.ts'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'

import Heading from '@/components/ui/Typography/Heading.tsx'

const ServerActivity = () => {
    const { serverUuid } = Route.useParams()
    const { page, setPage } = usePagination()

    const { data, isLoading, isError, refetch } = useQuery(
        serverAuditQueries.list(serverUuid, { page })
    )

    return (
        <>
            <div className={'flex flex-wrap items-center justify-between gap-2'}>
                <Heading>Activity</Heading>
            </div>
            <p className={'text-sm text-muted-foreground'}>
                Recent actions taken on this server, including those taken by
                staff.
            </p>
            <AuditFeed
                data={data}
                isLoading={isLoading}
                isError={isError}
                onRetry={refetch}
                page={page}
                onPageChange={setPage}
                emptyDescription={
                    'Actions taken on this server will be listed here as they happen.'
                }
            />
        </>
    )
}

export const Route = createLazyFileRoute('/_app/servers/$serverUuid/activity')({
    component: ServerActivity,
    // @ts-ignore
    meta: () => [{ title: 'Activity' }],
})
