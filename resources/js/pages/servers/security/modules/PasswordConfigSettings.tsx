import FormBlock from '@/components/FormBlock'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { ServerContext } from '@/pages/servers/Show'
import { formDataHandler } from '@/util/helpers'
import { Inertia } from '@inertiajs/inertia'
import { useForm } from '@inertiajs/inertia-react'
import {
  Badge,
  Button,
  Paper,
  Radio,
  RadioGroup,
  SegmentedControl,
  Textarea,
  TextInput,
} from '@mantine/core'
import { ChangeEvent, FormEvent, useContext, useEffect } from 'react'

const PasswordConfigSettings = () => {
  const settingsContext = useContext(SettingsContext)

  const { data, setData, post, processing, errors, reset } = useForm({
    _method: 'PUT',
    type: 'sshkeys',
    contents: '',
    password: '',
    password_confirmation: '',
  })

  const submit = (e: FormEvent<HTMLFormElement>) => {
    post(route('servers.show.settings.password', settingsContext?.server.id))
  }

  useEffect(() => {
    setData(
      'contents',
      decodeURIComponent(settingsContext?.config.sshkeys || '')
    )
  }, [])

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  return (
    <FormBlock
      title='Authentication'
      inputs={
        <div className='flex flex-col space-y-3'>
          <span>
          {settingsContext?.config.ciuser && (
            <Badge styles={{
              inner: {
                textTransform: 'none'
              }
            }}>{settingsContext?.config.ciuser}</Badge>
          )}</span>
          <SegmentedControl
            data={[
              { label: 'Public Key', value: 'sshkeys' },
              { label: 'Password', value: 'cipassword' },
            ]}
            value={data.type}
            onChange={(e) => setData('type', e)}
            className='mt-1'
          />

          {data.type === 'sshkeys' && (
            <Textarea
              label='Public Key'
              placeholder='ssh-rsa ...'
              value={data.contents}
              onChange={(e) => setData('contents', e.target.value)}
              autosize
              maxRows={4}
              required
            />
          )}

          {data.type === 'cipassword' && (
            <>
              <TextInput
                label='Password'
                name='password'
                type='password'
                value={data.password}
                className='block w-full'
                onChange={onHandleChange}
                error={errors.password}
                required
              />

              <TextInput
                label='Confirm Password'
                name='password_confirmation'
                type='password'
                value={data.password_confirmation}
                className='block w-full'
                onChange={onHandleChange}
                error={errors.password_confirmation}
                required
              />
            </>
          )}
        </div>
      }
      defaultAction
      onSubmit={submit}
      processing={processing}
    />
  )
}

export default PasswordConfigSettings
