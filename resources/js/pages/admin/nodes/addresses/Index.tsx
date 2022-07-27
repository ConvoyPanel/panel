import { Address } from '@/api/admin/nodes/addresses/types'
import { Node } from '@/api/admin/nodes/types'
import { Server } from '@/api/server/types'
import { DefaultProps } from '@/api/types/default'
import EmptyState from '@/components/EmptyState'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import { formDataHandler } from '@/util/helpers'
import { LinkIcon } from '@heroicons/react/outline'
import { Head, useForm } from '@inertiajs/inertia-react'
import {
  Button,
  Group,
  Modal,
  Paper,
  Radio,
  RadioGroup,
  Select,
  Table,
  Text,
  TextInput,
} from '@mantine/core'
import { debounce } from 'lodash'
import { ChangeEvent, forwardRef, useCallback, useState } from 'react'
import getSearchNodes from '@/api/admin/nodes/searchNodes'
import getSearchServers from '@/api/admin/servers/searchServers'
import SelectItem from '@/components/SelectItem'

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
    node_id: node.id,
    address: '',
    subnet_mask: '',
    gateway: '',
    type: 'ip',
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const handleCreate = async () => {
    post(route('admin.nodes.show.addresses.store', node.id), {
      onSuccess: () => {
        reset()
        setShowCreateModal(false)
      },
    })
  }

  /* const [nodes, setNodes] = useState<Node[]>([])
  const searchNodes = useCallback(
    debounce(async (query: string) => {
      const { data } = await getSearchNodes(query)
      setNodes(data)
    }, 500),
    []
  ) */

  const [servers, setServers] = useState<
    {
      label: string
      value: string
      description: string
    }[]
  >([])
  const searchServers = useCallback(
    debounce(async (query: string) => {
      const { data } = await getSearchServers(query)
      setServers(
        data.map((server) => {
          return {
            label: server.name,
            value: server.id.toString(),
            description: `Node: ${
              server.node.name
            }, VMID: ${server.vmid.toString()}`,
          }
        })
      )
    }, 500),
    []
  )

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
            <div className='flex flex-col space-y-3'>
              <Select
                label='Server'
                placeholder='Search'
                searchable
                itemComponent={SelectItem}
                clearable
                nothingFound='No options'
                onSearchChange={searchServers}
                onChange={(e) => setData('server_id', parseInt(e as string))}
                description="Can leave empty. This option doesn't automatically assign the address to a server but marks it as linked."
                data={servers}
                error={errors.server_id}
              />
              <RadioGroup
                label='IP Type'
                value={data.type}
                error={errors.type}
                //@ts-ignore
                onChange={(value) => setData('type', value)}
              >
                <Radio label='IPv4' value='ip' />
                <Radio label='IPv6' value='ip6' />
              </RadioGroup>
              <TextInput
                label='IP Address'
                name='address'
                value={data.address}
                autoFocus
                onChange={onHandleChange}
                error={errors.address}
                required
              />
              <TextInput
                label='Subnet Mask'
                name='subnet_mask'
                value={data.subnet_mask}
                autoFocus
                onChange={onHandleChange}
                error={errors.subnet_mask}
                required
              />
              <TextInput
                label='Gateway'
                name='gateway'
                value={data.gateway}
                autoFocus
                onChange={onHandleChange}
                error={errors.gateway}
                required
              />
            </div>
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
                  <td>{address.type === 'ip' ? 'IPv4' : 'IPv6'}</td>
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
