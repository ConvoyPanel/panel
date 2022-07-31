import { formDataHandler } from '@/util/helpers'
import {
  Button,
  Modal,
  Radio,
  RadioGroup,
  Select,
  TextInput,
} from '@mantine/core'
import { Head, useForm } from '@inertiajs/inertia-react'
import { ChangeEvent, useCallback, useState } from 'react'
import { debounce } from 'lodash'
import SelectItem from '@/components/SelectItem'
import { Node } from '@/api/admin/nodes/types'
import getSearchServers from '@/api/admin/servers/searchServers'

export interface FormData {
  server_id?: number
  node_id?: number
  address: string
  cidr: string
  gateway: string
  type: 'ip' | 'ip6'
}

export interface Props {
    node: Node,
    open: boolean,
    setOpen: (show: boolean) => void

}

const NewAddressModal = ({node, open, setOpen}: Props) => {
  const { data, setData, post, processing, errors, reset } = useForm<FormData>({
    server_id: undefined,
    node_id: node.id,
    address: '',
    cidr: '',
    gateway: '',
    type: 'ip',
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const handleCreate = async () => {
    post(route('admin.nodes.show.addresses', node.id), {
      onSuccess: () => {
        reset()
        setOpen(false)
      },
    })
  }

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
    <Modal
      opened={open}
      onClose={() => setOpen(false)}
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
            value={data.server_id?.toString()}
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
            label='CIDR (/xx)'
            name='cidr'
            placeholder='xx'
            value={data.cidr}
            onChange={onHandleChange}
            error={errors.cidr}
            required
          />
          <TextInput
            label='Gateway'
            name='gateway'
            value={data.gateway}
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
  )
}

export default NewAddressModal
