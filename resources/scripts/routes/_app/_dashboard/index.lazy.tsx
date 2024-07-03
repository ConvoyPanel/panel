import usePagination from '@/hooks/use-pagination.ts'
import { createLazyFileRoute } from '@tanstack/react-router'

import useServersSWR from '@/api/servers/use-servers-swr.ts'

import ServerCard from '@/components/interfaces/Client/Dashboard/ServerCard.tsx'

import LengthAwarePaginator from '@/components/ui/Pagination/LengthAwarePaginator.tsx'
import { Heading } from '@/components/ui/Typography'


export const Route = createLazyFileRoute('/_app/_dashboard/')({
    component: Dashboard,
})

function Dashboard() {
    const { page, setPage } = usePagination()
    const { data } = useServersSWR(page)

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
