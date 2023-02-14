import { User } from '@/api/admin/users/getUsers'
import useUsersSWR from '@/api/admin/users/useUsersSWR'
import CreateUserButton from '@/components/admin/users/CreateUserButton'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import TextInput from '@/components/elements/inputs/TextInput'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import usePagination from '@/util/usePagination'
import { CheckIcon, MagnifyingGlassIcon } from '@heroicons/react/20/solid'
import { useDebouncedValue } from '@mantine/hooks'
import { useState } from 'react'
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
    const [query, setQuery] = useState('')
    const [debouncedQuery] = useDebouncedValue(query, 200)
    const [page, setPage] = usePagination()
    const { data } = useUsersSWR({ page, query: debouncedQuery })

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title='Users' showFlashKey='admin:users'>
                <div className='flex space-x-2 items-center mb-3'>
                    <TextInput
                        icon={<MagnifyingGlassIcon className='text-accent-400 w-4 h-4' />}
                        className='grow'
                        value={query}
                        onChange={e => setQuery(e.target.value)}
                        placeholder='Search...'
                    />
                    <CreateUserButton />
                </div>
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
