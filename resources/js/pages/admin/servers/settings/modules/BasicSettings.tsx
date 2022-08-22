import FormBlock from '@/components/FormBlock'
import SelectItem from '@/components/SelectItem'
import { SettingsContext } from '@/pages/admin/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Checkbox, NumberInput, Paper, Select, TextInput } from '@mantine/core'
import { debounce } from 'lodash'
import {
  ChangeEvent,
  FormEvent,
  useCallback,
  useContext,
  useEffect,
  useState,
} from 'react'
import getSearchNodes from '@/api/admin/nodes/searchNodes'
import getSearchUsers from '@/api/admin/users/searchUsers'

const BasicSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, put, processing, errors, reset } = useForm({
    name: settingsContext?.server.name,
    user_id: settingsContext?.server.user_id,
    node_id: settingsContext?.server.node_id,
    vmid: settingsContext?.server.vmid,
    is_template: settingsContext?.server.template ? true : false,
    is_visible: settingsContext?.server.template?.visible ? true : false,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    put(
      route(
        'admin.servers.show.settings.basic-info',
        settingsContext?.server.id
      )
    )
  }

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const [nodes, setNodes] = useState<
    {
      label: string
      value: string
      description: string
    }[]
  >([])

  const searchNodes = useCallback(
    debounce(async (query: string) => {
      const { data: { data } } = await getSearchNodes(query)
      setNodes(
        data.map((node) => {
          return {
            label: node.name,
            value: node.id.toString(),
            description: node.hostname,
          }
        })
      )
    }, 500),
    []
  )

  const [users, setUsers] = useState<
    {
      label: string
      value: string
      description: string
    }[]
  >([])

  const searchUsers = useCallback(
    debounce(async (query: string) => {
      const { data: { data } } = await getSearchUsers(query)
      setUsers(
        data.map((user) => {
          return {
            label: user.name,
            value: user.id.toString(),
            description: user.email,
          }
        })
      )
    }, 500),
    []
  )

  return (
    <FormBlock
      title='Basic Settings'
      inputs={
        <div className='flex flex-col space-y-3'>
          <TextInput
            label='Name'
            name='name'
            value={data.name}
            onChange={onHandleChange}
            error={errors.name}
            required
          />

          <Select
            label='Node'
            placeholder='Search'
            searchable
            itemComponent={SelectItem}
            clearable
            nothingFound='No options'
            value={data.node_id?.toString()}
            onSearchChange={searchNodes}
            onChange={(e) => setData('node_id', parseInt(e as string))}
            data={nodes}
            error={errors.node_id}
            required
          />

          <Select
            label='User'
            placeholder='Search'
            searchable
            itemComponent={SelectItem}
            clearable
            nothingFound='No options'
            value={data.user_id?.toString()}
            onSearchChange={searchUsers}
            onChange={(e) => setData('user_id', parseInt(e as string))}
            data={users}
            error={errors.user_id}
            required
          />

          <TextInput
            label='VMID'
            name='vmid'
            value={data.vmid}
            onChange={onHandleChange}
            error={errors.vmid}
            required
          />

            <Checkbox
              checked={data.is_template}
              onChange={(e) => setData('is_template', e.target.checked)}
              label='Mark as template'
            />

          {data.is_template ? (
            <Checkbox
              checked={data.is_visible}
              onChange={(e) => setData('is_visible', e.target.checked)}
              label='Make template visible'
            />
          ) : (
            ''
          )}
        </div>
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default BasicSettings
