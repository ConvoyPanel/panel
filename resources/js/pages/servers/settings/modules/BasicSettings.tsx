import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Paper, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const BasicSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, post, processing, errors, reset } = useForm({
    _method: 'patch',
    name: settingsContext?.server.name,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    post(
      route('servers.show.settings.basic-info', settingsContext?.server.id)
    )
  }

  useEffect(() => {
    if (!processing) {
      Inertia.reload({ only: ['server'] })
    }
  }, [processing])

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) => formDataHandler(event, setData)

  return (
    <FormBlock
      title='Basic Settings'
      inputs={
        <TextInput
          label='Name'
          name='name'
          value={data.name}
          className='mt-1 block w-full'
          onChange={onHandleChange}
          error={errors.name}
          required
        />
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default BasicSettings
