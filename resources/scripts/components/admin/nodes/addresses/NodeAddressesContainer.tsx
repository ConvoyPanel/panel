import usePagination from '@/util/usePagination'
import { useState } from 'react'
import { Link } from 'react-router-dom'

import useAddressesSWR from '@/api/admin/nodes/addresses/useAddressesSWR'
import useNodeSWR from '@/api/admin/nodes/useNodeSWR'
import { Address } from '@/api/server/getServer'

import Menu from '@/components/elements/Menu'
import Pagination from '@/components/elements/Pagination'
import Spinner from '@/components/elements/Spinner'
import Table, {
    Actions,
    ColumnArray,
    RowActionsProps,
} from '@/components/elements/displays/Table'

import EditAddressModal from '@/components/admin/ipam/addresses/EditAddressModal'
import NodeContentBlock from '@/components/admin/nodes/NodeContentBlock'
import DeleteAddressModal from '@/components/admin/nodes/addresses/DeleteAddressModal'


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
        cell: ({ value }) =>
            value ? (
                <Link
                    to={`/admin/servers/${value.id}`}
                    className='link text-foreground'
                >
                    {value.hostname}
                </Link>
            ) : null,
    },
]

const NodeAddressesContainer = () => {
    const { data: node } = useNodeSWR()
    const [page, setPage] = usePagination()
    const { mutate, data } = useAddressesSWR(node.id, {
        page,
        include: ['server'],
    })

    const rowActions = ({ row }: RowActionsProps<Address>) => {
        const [showEditModal, setShowEditModal] = useState(false)
        const [showDeleteModal, setShowDeleteModal] = useState(false)

        return (
            <>
                <EditAddressModal
                    address={showEditModal ? row : null}
                    onClose={() => setShowEditModal(false)}
                    mutate={mutate}
                />
                <DeleteAddressModal
                    address={row}
                    open={showDeleteModal}
                    onClose={() => setShowDeleteModal(false)}
                />
                <Actions>
                    <Menu.Item onClick={() => setShowEditModal(true)}>
                        Edit
                    </Menu.Item>
                    <Menu.Divider />
                    <Menu.Item
                        color='red'
                        onClick={() => setShowDeleteModal(true)}
                    >
                        Delete
                    </Menu.Item>
                </Actions>
            </>
        )
    }

    return (
        <div className='bg-background min-h-screen'>
            <NodeContentBlock title='Addresses'>
                {!data ? (
                    <Spinner />
                ) : (
                    <Pagination data={data} onPageSelect={setPage}>
                        {({ items }) => (
                            <Table
                                rowActions={rowActions}
                                columns={columns}
                                data={items}
                            />
                        )}
                    </Pagination>
                )}
            </NodeContentBlock>
        </div>
    )
}

export default NodeAddressesContainer
