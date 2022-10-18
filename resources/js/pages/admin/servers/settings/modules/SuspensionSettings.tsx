import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/servers/settings/Index'
import { useForm } from '@inertiajs/inertia-react'
import { Badge, Button, Checkbox, Modal, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect, useState } from 'react'

const SuspensionSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { post, processing } = useForm()

  const submit = (e: FormEvent<HTMLFormElement>) => {
    post(route('admin.servers.show.settings.suspension', settingsContext?.server.id))
  }

  return (
    <FormBlock
      title='Suspend Server'
      inputs={
        <div className='space-y-3'>
          {settingsContext?.server.status === 'suspended' && (
            <Badge>Suspended</Badge>
          )}
          <p className='p-desc'>
            Suspend the server and prevent any additional actions from being
            taken on it.
          </p>
        </div>
      }
      actions={
        <Button type='submit' loading={processing} color='red'>
          Toggle Suspension
        </Button>
      }
      onSubmit={submit}
    />
  )
}

export default SuspensionSettings
