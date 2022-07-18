import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Paper, Radio, RadioGroup, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const PasswordConfigSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, put, processing, errors, reset } = useForm({
    type: 'sshkeys',
    password: '',
    password_confirmation: '',
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    put(
      route('servers.show.settings.update-password', settingsContext?.server.id)
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
      title='Auth Configuration'
      inputs={
        <>
          <RadioGroup
            value={data.type}
            error={errors.type}
            //@ts-ignore
            onChange={(value) => setData('type', value)}
          >
            <Radio label='Public Key' value='sshkeys' />
            <Radio label='Password (not recommended)' value='cipassword' />
          </RadioGroup>

          <TextInput
            label='Contents'
            name='password'
            type='password'
            value={data.password}
            className='mt-1 block w-full'
            onChange={onHandleChange}
            error={errors.password}

          />

          <TextInput
            label='Confirm Contents'
            name='password_confirmation'
            type='password'
            value={data.password_confirmation}
            className='mt-1 block w-full'
            onChange={onHandleChange}
            error={errors.password_confirmation}

          />
        </>
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default PasswordConfigSettings
