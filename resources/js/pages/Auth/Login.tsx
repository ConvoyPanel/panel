import React, { ChangeEvent, FormEvent, useEffect } from 'react'
import { Button, TextInput } from '@mantine/core'
import Checkbox from '@/components/Checkbox'
import Guest from '@/components/layouts/Guest'
import Input from '@/components/Input'
import Label from '@/components/Label'
import ValidationErrors from '@/components/ValidationErrors'
import { Head, Link, useForm } from '@inertiajs/inertia-react'

interface Props {
  status?: string
  canResetPassword: boolean
}

export default function Login({ status, canResetPassword }: Props) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email: '',
    password: '',
    remember: '',
  })

  useEffect(() => {
    return () => {
      reset('password')
    }
  }, [])

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) => {
    setData(
    // @ts-ignore
      event.target.name,
      event.target.type === 'checkbox'
        ? event.target.checked
        : event.target.value
    )
  }

  const submit = (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault()

    post(route('login'))
  }

  return (
    <Guest>
      <Head title='Log in' />

      {status && (
        <div className='mb-4 font-medium text-sm text-green-600'>{status}</div>
      )}

      <ValidationErrors errors={errors} />

      <form onSubmit={submit}>
        <div>
          <TextInput
            label='Email'
            name='email'
            value={data.email}
            styles={{
                required: { display: 'none'}
             }}
            className='mt-1 block w-full'
            autoComplete='username'
            autoFocus
            onChange={onHandleChange}
            required
          />
        </div>

        <div className='mt-4'>
          <TextInput
            label='Password'
            name='password'
            styles={{
                required: { display: 'none'}
             }}
            type='password'
            value={data.password}
            className='mt-1 block w-full'
            autoComplete='current-password'
            onChange={onHandleChange}
            required
          />
        </div>

        <div className='block mt-4'>
          <label className='flex items-center'>
            <Checkbox
              name='remember'
              value={data.remember}
              handleChange={onHandleChange}
            />

            <span className='ml-2 text-sm text-gray-600'>Remember me</span>
          </label>
        </div>

        <div className='flex items-center justify-end mt-4'>
          {canResetPassword && (
            <Link
              href={route('password.request')}
              className='underline text-sm text-gray-600 hover:text-gray-900'
            >
              Forgot your password?
            </Link>
          )}

          <Button type='submit' className='ml-4' loading={processing}>
            Log in
          </Button>
        </div>
      </form>
    </Guest>
  )
}
