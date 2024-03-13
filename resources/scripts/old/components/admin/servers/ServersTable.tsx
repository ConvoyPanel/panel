import usePagination from '@/util/usePagination'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router-dom'

import useServersSWR from '@/api/admin/servers/useServersSWR'
import { ServerBuild } from '@/api/server/getServer'

import Menu from '@/components/elements/Menu'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import Table, {
    Actions,
    ColumnArray,
} from '@/components/elements/displays/Table'


interface Props {
    query?: string
    className?: string
    nodeId?: number
    userId?: number
}

const ServersTable = ({ query, className, nodeId, userId }: Props) => {
    const { t: tStrings } = useTranslation('strings')
    const [page, setPage] = usePagination()
    const { data } = useServersSWR({
        page,
        query,
        nodeId,
        userId,
        include: ['node', 'user'],
    })

    const columns: ColumnArray<ServerBuild> = [
        {
            accessor: 'name',
            header: tStrings('name'),
            cell: ({ value, row }) => (
                <Link
                    to={`/admin/servers/${row.id}`}
                    className='link text-foreground'
                >
                    {value}
                </Link>
            ),
        },
        {
            accessor: 'hostname',
            header: tStrings('hostname'),
        },
        {
            accessor: 'user',
            header: tStrings('owner'),
            cell: ({ value }) => (
                <Link
                    to={`/admin/users/${value!.id}/settings`}
                    className='link text-foreground'
                >
                    {value!.email}
                </Link>
            ),
        },
        {
            accessor: 'node',
            header: tStrings('node'),
            cell: ({ value }) => value!.name,
        },
    ]

    const rowActions = ({ row: server }: RowActionsProps<ServerBuild>) => {
        return (
            <Actions>
                <Menu.Item
                    onClick={() => navigator.clipboard.writeText(server.uuid)}
                >
                    Copy ID
                </Menu.Item>
            </Actions>
        )
    }

    return (
        <div className={className}>
            {!data ? (
                <Spinner />
            ) : (
                <Pagination data={data} onPageSelect={setPage}>
                    {({ items }) => (
                        <Table
                            columns={columns}
                            data={items}
                            rowActions={rowActions}
                        />
                    )}
                </Pagination>
            )}
        </div>
    )
}

export default ServersTable
