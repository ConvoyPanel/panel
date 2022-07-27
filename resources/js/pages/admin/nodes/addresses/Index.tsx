import { Address } from '@/api/nodes/addresses/types'
import { Node } from '@/api/nodes/types'
import { DefaultProps } from '@/api/types/default'
import EmptyState from '@/components/EmptyState'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import { formDataHandler } from '@/util/helpers'
import { LinkIcon } from '@heroicons/react/outline'
import { Head, useForm } from '@inertiajs/inertia-react'
import { Button, Modal, Paper, Table, TextInput } from '@mantine/core'
import { ChangeEvent, useState } from 'react'

interface Props extends DefaultProps {
  node: Node
  addresses: Address[]
}

interface FormData {
  server_id?: number
  node_id?: number
  address: string
  subnet_mask: string
  gateway: string
  type: 'ip' | 'ip6'
}

const Index = ({ auth, node, addresses }: Props) => {
  const [showCreateModal, setShowCreateModal] = useState(false)

  const { data, setData, post, processing, errors, reset } = useForm<FormData>({
    server_id: undefined,
    node_id: undefined,
    address: '',
    subnet_mask: '',
    gateway: '',
    type: 'ip',
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const handleCreate = async () => {}

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{node.name}</h1>}
      secondaryHeader={<NodeNav id={node.id} />}
    >
      <Head title={`${node.name} - Addresses`} />

      <Main>
        <Modal
          opened={showCreateModal}
          onClose={() => setShowCreateModal(false)}
          title={`Import a new address`}
          centered
        >
          <form
            onSubmit={(e) => {
              e.preventDefault()
              handleCreate()
            }}
          >

            {/* <TextInput
              label='Name'
              name='name'
              value={data.name}
              styles={{
                required: { display: 'none' },
              }}
              className='mt-1 block w-full'
              autoFocus
              onChange={onHandleChange}
              error={errors.name}
              required
            /> */}
            <Button
              type='submit'
              loading={processing}
              className='mt-3'
              fullWidth
              onClick={() => handleCreate()}
            >
              Create
            </Button>
          </form>
        </Modal>
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
              onClick={() => setShowCreateModal(true)}
            />
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Index
