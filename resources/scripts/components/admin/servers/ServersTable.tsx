import useServersSWR from '@/api/admin/servers/useServersSWR'
import { Server } from '@/api/server/getServer'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import usePagination from '@/util/usePagination'
import { Link } from 'react-router-dom'
import { useState } from 'react'
import Button from '@/components/elements/Button'

interface Props {
    className?: string
    nodeId?: number
}

const columns: ColumnArray<Server> = [
    {
        accessor: 'name',
        header: 'Name',
        cell: ({ value, row }) => (
            <Link to={`/admin/servers/${row.id}`} className='link text-foreground'>
                {value}
            </Link>
        ),
    },
    {
        accessor: 'hostname',
        header: 'Hostname',
    },
    {
        accessor: 'user',
        header: 'Owner',
        cell: ({ value }) => value!.name,
    },
    {
        accessor: 'node',
        header: 'Node',
        cell: ({ value }) => value!.name,
    },
]

const ServersTable = ({ className, nodeId }: Props) => {
    const [page, setPage] = usePagination()
    const { data, mutate } = useServersSWR({ page, nodeId, includes: ['node', 'user'] })

    return (
        <div className={className}>
            {!data ? (
                <Spinner />
            ) : (
                <Pagination data={data} onPageSelect={setPage}>
                    {({ items }) => <Table columns={columns} data={items} />}
                </Pagination>
            )}
        </div>
    )
}

export default ServersTable
