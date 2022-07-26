import { Address } from '@/api/nodes/addresses/types'
import { Node } from '@/api/nodes/types'
import { DefaultProps } from '@/api/types/default'
import EmptyState from '@/components/EmptyState'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import { LinkIcon } from '@heroicons/react/outline'
import { Head } from '@inertiajs/inertia-react'
import { Button, Paper, Table } from '@mantine/core'

interface Props extends DefaultProps {
  node: Node
  addresses: Address[]
}

const Index = ({ auth, node, addresses }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{node.name}</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Addresses`} />

      <Main>
        <h3 className='h3-deemphasized'>IP Addresses</h3>
        <Paper shadow='xs' className='p-card w-full'>
          <div className='flex justify-end'>
            <Button>New Address</Button>
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
                <tr key={address.id}>
                  <td>{address.address}</td>
                  <td>{address.subnet_mask}</td>
                  <td>{address.gateway}</td>
                  <td>{address.type === 'ip' ? 'ipv4' : 'ipv6'}</td>
                  <td>{address.server_id ? address.server_id : 'Unlinked'}</td>
                  <td></td>
                </tr>
              ))}
            </tbody>
          </Table>
          {addresses.length === 0 && (
            <EmptyState
              icon={LinkIcon}
              title='No Addresses'
              description='Get started by creating a new address.'
              action='New Address'
              onClick={() => {}}
            />
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Index
