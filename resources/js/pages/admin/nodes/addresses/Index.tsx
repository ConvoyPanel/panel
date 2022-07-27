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
import {
  Button,
  Paper,
  Table,
} from '@mantine/core'
import {  useState } from 'react'
import getSearchNodes from '@/api/admin/nodes/searchNodes'
import NewAddressModal from '@/components/nodes/addresses/NewAddressModal'

interface Props extends DefaultProps {
  node: Node
  addresses: Address[]
}

const AddressRow = ({ address }: { address: Address }) => {
  return <tr key={address.id}>
    <td>{address.address}</td>
    <td>{address.subnet_mask}</td>
    <td>{address.gateway}</td>
    <td>{address.type === 'ip' ? 'IPv4' : 'IPv6'}</td>
    <td>{address.server_id ? address.server_id : 'Unlinked'}</td>
    <td></td>
  </tr>
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
        <NewAddressModal node={node} showCreateModal={showCreateModal} setShowCreateModal={setShowCreateModal} />
        <h3 className='h3-deemphasized'>IP Addresses</h3>
        <Paper shadow='xs' className='p-card w-full'>
          <div className='flex justify-end'>
            <Button onClick={() => setShowCreateModal(true)}>
              New Address
            </Button>
          </div>
          <Table className='mt-3' striped highlightOnHover>
            <thead>
              <tr>
                <th>Address</th>
                <th>Subnet Mask</th>
                <th>Gateway</th>
                <th>Type</th>
                <th>Linked Server</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {addresses.map((address) => (
                <AddressRow key={address.id} address={address} />
              ))}
            </tbody>
          </Table>
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
