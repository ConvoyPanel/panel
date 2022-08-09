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
import { Address } from '@/api/admin/nodes/addresses/types'

export interface FormData {
  server_id?: number
  node_id?: number
  address: string
  cidr: string
  gateway: string
  mac_address?: string
  type: string
}

export interface Props {
  node: Node
  address: Address
  open: boolean
  setOpen: (show: boolean) => void
}

const EditAddressModal = ({ node, address, open, setOpen }: Props) => {
  const { data, setData, put, processing, errors, reset } = useForm<FormData>({
    server_id: address.server_id,
    node_id: node.id,
    address: address.address,
    cidr: address.cidr,
    gateway: address.gateway,
    mac_address: address.mac_address,
    type: address.type,
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const handleCreate = async () => {
    put(
      route('admin.nodes.show.addresses.show', {
        node: node.id,
        address: address.id,
      }),
      {
        onSuccess: () => {
          reset()
          setOpen(false)
        },
      }
    )
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
      title={`Edit address ${address.address}`}
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
            value={data.server_id?.toString()}
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
            <Radio label='IPv4' value='ipv4' />
            <Radio label='IPv6' value='ipv6' />
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
            label='CIDR'
            name='cidr'
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
          {data.type === 'ipv4' && (
            <TextInput
              label='Mac Address'
              name='mac_address'
              value={data.mac_address}
              onChange={onHandleChange}
              error={errors.mac_address}
            />
          )}
        </div>
        <Button
          type='submit'
          loading={processing}
          className='mt-3'
          fullWidth
          onClick={() => handleCreate()}
        >
          Update
        </Button>
      </form>
    </Modal>
  )
}

export default EditAddressModal
