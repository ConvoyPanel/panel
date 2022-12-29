import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'
import { Address } from '@/api/server/getServer'
import EditAddressModal from '@/components/admin/nodes/addresses/EditAddressModal'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import Button from '@/components/elements/Button'
import Table, { ColumnArray } from '@/components/elements/displays/Table'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import { NodeContext } from '@/state/admin/node'
import usePagination from '@/util/usePagination'
import { useState } from 'react'
import { Link } from 'react-router-dom'

const columns: ColumnArray<Address> = [
    {
        accessor: 'address',
        header: 'Address',
    },
    {
        accessor: 'cidr',
        header: 'CIDR',
    },
    {
        accessor: 'gateway',
        header: 'Gateway',
    },
    {
        accessor: 'macAddress',
        header: 'MAC Address',
    },
    {
        accessor: 'type',
        header: 'Type',
    },
    {
        accessor: 'server',
        header: 'Server',
        cell: ({ value }) => (value ? <Link to={`/admin/servers/${value.id}`} className='link text-foreground'>{value.hostname}</Link> : null),
    },
]

const NodeAddressesContainer = () => {
    const nodeId = NodeContext.useStoreState(state => state.node.data!.id)
    const [page, setPage] = usePagination()
    const { mutate, data } = useAddressesSWR(nodeId, { page, includes: ['server'] })
    const [open, setOpen] = useState(false)

    return (
        <div className='bg-background min-h-screen'>
            <NodeContentBlock title='Addresses' showFlashKey='admin:node:addresses'>
                <EditAddressModal open={open} onClose={() => setOpen(false)} />
                <div className='flex justify-end items-center mb-3'>
                    <Button onClick={() => setOpen(true)} variant='filled'>
                        New Address
                    </Button>
                </div>
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => <Table columns={columns} data={items} />}
                    </Pagination>
                )}
            </NodeContentBlock>
        </div>
    )
}

export default NodeAddressesContainer
