import { Address } from '@/api/admin/nodes/addresses/types'
import { Node } from '@/api/admin/nodes/types'
import { Server } from '@/api/server/types'
import { DefaultProps } from '@/api/types/default'
import EmptyState from '@/components/EmptyState'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import { LinkIcon } from '@heroicons/react/outline'
import { Head, useForm } from '@inertiajs/inertia-react'
import { Button, Modal, Paper, Table } from '@mantine/core'
import { useState } from 'react'
import getSearchNodes from '@/api/admin/nodes/searchNodes'
import NewAddressModal from '@/components/nodes/addresses/NewAddressModal'
import EditButton from '@/components/elements/tables/EditButton'
import DeleteButton from '@/components/elements/tables/DeleteButton'
import EditAddressModal from '@/components/nodes/addresses/EditAddressModal'

interface Props extends DefaultProps {
  node: Node
  addresses: Address[]
}

interface AddressRowProps {
  address: Address
  node: Node
}

const AddressRow = ({ address, node }: AddressRowProps) => {
  const [showEditModal, setShowEditModal] = useState(false)
  const [showDeleteModal, setShowDeleteModal] = useState(false)

  const { processing, delete: processDelete } = useForm({
    id: address.id,
  })

  const handleDelete = async () => {
    processDelete(
      route('admin.nodes.show.addresses.destroy', {
        node: node.id,
        address: address.id,
      }),
      {
        onSuccess: () => {
          setShowDeleteModal(false)
        },
      }
    )
  }

  return (
    <>
      <EditAddressModal
        node={node}
        address={address}
        open={showEditModal}
        setOpen={setShowEditModal}
      />
      <Modal
        opened={showDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        title={`Delete ${address.address}?`}
        centered
      >
        <p className='p-desc'>
          Are you sure you want to delete this address? This action will only
          delete it from the database but not from the node/server.
        </p>

        <Button
          loading={processing}
          color='red'
          className='mt-3'
          fullWidth
          onClick={() => handleDelete()}
        >
          Delete
        </Button>
      </Modal>
      <tr key={address.id}>
        <td>{address.address}</td>
        <td>/{address.cidr}</td>
        <td>{address.gateway}</td>
        <td>{address.type === 'ip' ? 'IPv4' : 'IPv6'}</td>
        <td>{address.server_id ? address.server_id : 'Unlinked'}</td>
        <td>
          <EditButton onClick={() => setShowEditModal(true)} />
          <DeleteButton onClick={() => setShowDeleteModal(true)} />
        </td>
      </tr>
    </>
  )
}

const Index = ({ auth, node, addresses }: Props) => {
  const [showCreateModal, setShowCreateModal] = useState(false)

  /* const [nodes, setNodes] = useState<Node[]>([])
  const searchNodes = useCallback(
    debounce(async (query: string) => {
      const { data } = await getSearchNodes(query)
      setNodes(data)
    }, 500),
    []
  ) */

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{node.name}</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Addresses`} />

      <Main>
        <NewAddressModal
          node={node}
          open={showCreateModal}
          setOpen={setShowCreateModal}
        />
        <h3 className='h3-deemphasized'>IP Addresses</h3>
        <Paper shadow='xs' className='p-card w-full'>
          <div className='flex justify-end'>
            <Button onClick={() => setShowCreateModal(true)}>
              New Address
            </Button>
          </div>
          <div className='overflow-auto'>
            <Table className='mt-3' striped highlightOnHover>
              <thead>
                <tr>
                  <th>Address</th>
                  <th>CIDR</th>
                  <th>Gateway</th>
                  <th>Type</th>
                  <th>Linked Server</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {addresses.map((address) => (
                  <AddressRow key={address.id} node={node} address={address} />
                ))}
              </tbody>
            </Table>
          </div>
          {addresses.length === 0 && (
            <EmptyState
              icon={LinkIcon}
              title='No Addresses'
              description='Get started by creating a new address.'
              action='New Address'
              onClick={() => setShowCreateModal(true)}
            />
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Index
