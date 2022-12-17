import { Node } from '@/api/admin/nodes'
import useNodesSWR from '@/api/admin/nodes/useNodesSWR'
import CreateNodeModal from '@/components/admin/nodes/CreateNodeModal'
import Button from '@/components/elements/Button'
import Table, { Column, ColumnArray } from '@/components/elements/displays/Table'
import PageContentBlock from '@/components/elements/PageContentBlock'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import { bytesToString } from '@/util/helpers'
import usePagination from '@/util/usePagination'
import { useState } from 'react'

const columns: ColumnArray<Node> = [
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
        cell: ({ value }) => bytesToString(value),
    },
    {
        accessor: 'disk',
        header: 'Disk',
        cell: ({ value }) => bytesToString(value),
    },
    {
        accessor: 'serversCount',
        header: 'Servers',
        align: 'center',
    },
]

const NodesContainer = () => {
    const [page, setPage] = usePagination()
    const [open, setOpen] = useState(false)

    const { data } = useNodesSWR({ page })

    return (
        <div className='bg-background min-h-screen'>
            <PageContentBlock title='Nodes' showFlashKey='admin:nodes'>
                <CreateNodeModal open={open} onClose={() => setOpen(false)} />
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New Node
                    </Button>
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

export default NodesContainer
