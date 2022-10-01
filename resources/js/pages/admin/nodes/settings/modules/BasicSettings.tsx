import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/nodes/settings/Index'
import { formDataHandler } from '@/util/helpers'
import { useForm } from '@inertiajs/inertia-react'
import { Button, NumberInput, Paper, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const BasicSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, put, processing, errors, reset } = useForm({
    name: settingsContext?.node.name,
    cluster: settingsContext?.node.cluster,
    hostname: settingsContext?.node.hostname,
    token_id: '',
    secret: '',
    port: settingsContext?.node.port,
    network: settingsContext?.node.network,
    storage: settingsContext?.node.storage,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    put(route('admin.nodes.show.settings.basic-info', settingsContext?.node.id))
  }

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

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

          <div className='grid sm:grid-cols-3 gap-3'>
            <TextInput
              label='Hostname'
              name='hostname'
              value={data.hostname}
              className='block w-full sm:col-span-2'
              onChange={onHandleChange}
              error={errors.hostname}
              required
            />
            <NumberInput
              label='Port'
              name='port'
              value={data.port}
              className='block w-full sm:col-span-1'
              onChange={(e) => setData('port', e as number)}
              error={errors.port}
              required
            />
          </div>
          <TextInput
            label='Node Name (as appears under Nodes in Proxmox)'
            name='cluster'
            value={data.cluster}
            onChange={onHandleChange}
            error={errors.cluster}
            required
          />

          <div className='grid sm:grid-cols-1 gap-3'>
            <TextInput
              label='Network Interface'
              name='network'
              value={data.network}
              className='mt-1 block w-full'
              onChange={onHandleChange}
              error={errors.network}
              placeholder='vmbr1'
              required
            />
            <TextInput
              label='Volume Storage'
              name='storage'
              value={data.storage}
              className='mt-1 block w-full'
              onChange={onHandleChange}
              error={errors.storage}
              placeholder='local, local-lvm, etc'
              required
            />
          </div>

          <TextInput
            label='Token ID'
            name='token_id'
            placeholder='New token ID'
            value={data.token_id}
            onChange={onHandleChange}
            error={errors.token_id}
          />
          <TextInput
            label='Secret'
            name='secret'
            type='password'
            placeholder='New secret'
            value={data.secret}
            onChange={onHandleChange}
            error={errors.secret}
          />
        </div>
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default BasicSettings
