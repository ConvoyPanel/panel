import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/admin/users/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import { Button, Checkbox, NumberInput, Paper, TextInput } from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const BasicSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, post, put, processing, errors } = useForm({
    name: settingsContext?.user.name,
    email: settingsContext?.user.email,
    password: '',
    root_admin: settingsContext?.user.root_admin,
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    put(route('admin.users.show.settings', settingsContext?.user.id))
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
            placeholder='Eric Wang'
            value={data.name}
            onChange={onHandleChange}
            error={errors.name}
            required
          />
          <TextInput
            label='Email'
            name='email'
            type='email'
            placeholder='eric.w@example.com'
            value={data.email}
            onChange={onHandleChange}
            error={errors.email}
            required
          />
          <TextInput
            label='Password'
            name='password'
            type='password'
            placeholder='Change password'
            value={data.password}
            onChange={onHandleChange}
            error={errors.password}
          />
          <Checkbox
            checked={data.root_admin}
            onChange={(e) => setData('root_admin', e.target.checked)}
            label='Administrator'
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
