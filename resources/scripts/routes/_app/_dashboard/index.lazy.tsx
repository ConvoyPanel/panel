import ServerCard from '@/features/overview/components/client/ServerCard.tsx'
import { serverQueries } from '@/features/servers/api.ts'
import usePagination from '@/hooks/use-pagination.ts'
import { IconServerOff } from '@tabler/icons-react'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'

import { Card } from '@/components/ui/Card'
import {
    CollectionErrorState,
    SimpleEmptyState,
} from '@/components/ui/EmptyStates'
import { ItemGroup } from '@/components/ui/Item'
import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import Skeleton from '@/components/ui/Skeleton.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/_dashboard/')({
    component: Dashboard,
})

function Dashboard() {
    const { page, setPage } = usePagination()
    const { data, isLoading, isError, refetch } = useQuery(
        serverQueries.list({ page })
    )

    return (
        <>
            <Heading>My Servers</Heading>
            {isError && !data ? (
                <Card className={'py-6'}>
                    <CollectionErrorState onRetry={refetch} />
                </Card>
            ) : isLoading ? (
                <ItemGroup className={'gap-3'}>
                    {Array.from({ length: 4 }).map((_, index) => (
                        <Skeleton key={index} className={'h-24 w-full'} />
                    ))}
                </ItemGroup>
            ) : !data || data.items.length === 0 ? (
                <Card className={'py-6'}>
                    <SimpleEmptyState
                        icon={IconServerOff}
                        title={'No servers'}
                        description={
                            'Servers assigned to your account will appear here.'
                        }
                    />
                </Card>
            ) : (
                <LengthAwarePaginator
                    page={page}
                    data={data}
                    onPageChange={setPage}
                >
                    {({ items }) => (
                        <ItemGroup className={'gap-3'}>
                            {items.map(server => (
                                <ServerCard key={server.id} server={server} />
                            ))}
                        </ItemGroup>
                    )}
                </LengthAwarePaginator>
            )}
        </>
    )
}
