import { User } from '@/api/admin/users/getUsers'
import useUsersSWR from '@/api/admin/users/useUsersSWR'
import CreateUserButton from '@/components/admin/users/CreateUserButton'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import usePagination from '@/util/usePagination'
import { CheckIcon } from '@heroicons/react/20/solid'
import { Link } from 'react-router-dom'

const columns: ColumnArray<User> = [
    {
        accessor: 'name',
        header: 'Name',
        cell: ({ value, row }) => (
            <Link to={`/admin/users/${row.id}/settings`} className='link text-foreground'>
                {value}
            </Link>
        ),
    },
    {
        accessor: 'email',
        header: 'Email',
    },
    {
        accessor: 'rootAdmin',
        header: 'Administrator',
        align: 'center',
        cell: ({ value }) => (
            <div className='grid place-items-center'>
                {value ? <CheckIcon title='hidden' className='h-5 w-5 text-foreground' /> : null}
            </div>
        ),
    },
    {
        accessor: 'serversCount',
        header: 'Servers',
        align: 'center',
        cell: ({ value, row }) => (
            <Link to={`/admin/users/${row.id}/servers`} className='link text-foreground'>
                {value}
            </Link>
        ),
    },
]

const UsersContainer = () => {
    const [page, setPage] = usePagination()
    const { data, mutate } = useUsersSWR({ page })

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title='Users' showFlashKey='admin:users'>
                <CreateUserButton />
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table columns={columns} data={items} />}
                    </Pagination>
                )}
            </PageContentBlock>
        </div>
    )
}

export default UsersContainer
