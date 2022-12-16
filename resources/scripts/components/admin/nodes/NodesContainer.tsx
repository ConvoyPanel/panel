import { Node } from '@/api/admin/nodes'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'
import Table, { Column } from '@/components/elements/displays/Table'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import { bytesToString } from '@/util/helpers'
import usePagination from '@/util/usePagination'

const columns: Column<Node>[] = [
    {
        accessor: 'name',
        header: 'Name',
    },
    {
        accessor: 'fqdn',
        header: 'FQDN',
    },
    {
        accessor: 'memory',
        header: 'Memory',
        cell: ({ value }) => bytesToString(value as number)
    },
    {
        accessor: 'disk',
        header: 'Disk',
        cell: ({ value }) => bytesToString(value as number)
    },
    {
        accessor: 'serversCount',
        header: 'Servers',
        align: 'center'
    }
]

const NodesContainer = () => {
    const [page, setPage] = usePagination()

    const { data } = useNodesSWR({ page })

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title='Nodes' showFlashKey='admin:nodes'>
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

export default NodesContainer
