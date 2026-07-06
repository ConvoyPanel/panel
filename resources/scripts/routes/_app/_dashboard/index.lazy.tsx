import usePagination from '@/hooks/use-pagination.ts'
import { useQuery } from '@tanstack/react-query'
import { createLazyFileRoute } from '@tanstack/react-router'

import { serverQueries } from '@/features/servers/api.ts'

import ServerCard from '@/features/overview/components/client/ServerCard.tsx'

import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import { Heading } from '@/components/ui/Typography'

export const Route = createLazyFileRoute('/_app/_dashboard/')({
    component: Dashboard,
})

function Dashboard() {
    const { page, setPage } = usePagination()
    const { data } = useQuery(serverQueries.list({ page }))

    return (
        <>
            <Heading>My Servers</Heading>
            <LengthAwarePaginator
                page={page}
                data={data}
                onPageChange={setPage}
            >
                {({ items }) => (
                    <ul className={'space-y-3'}>
                        {items.map(server => (
                            <li key={server.id}>
                                <ServerCard key={server.id} server={server} />
                            </li>
                        ))}
                    </ul>
                )}
            </LengthAwarePaginator>
        </>
    )
}
