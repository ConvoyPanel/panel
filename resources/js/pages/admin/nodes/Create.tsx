import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head, Link, useForm } from '@inertiajs/inertia-react'
import { ArrowLeftIcon, ChevronLeftIcon, ExclamationCircleIcon } from '@heroicons/react/solid'
import {
  Button,
  Code,
  NumberInput,
  Overlay,
  Paper,
  TextInput,
  ActionIcon,
  Tooltip,
  Alert,
} from '@mantine/core'
import { ChangeEvent, FormEvent, useState } from 'react'
import { formDataHandler } from '@/util/helpers'
import { CheckCircleIcon } from '@heroicons/react/outline'
import { Inertia } from '@inertiajs/inertia'

interface Props extends DefaultProps {
}

const Create = ({ auth }: Props) => {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    cluster: '',
    hostname: '',
    token_id: '',
    secret: '',
    port: 8006,
  })

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    post(route('admin.nodes'))
  }

  return (
    <Authenticated auth={auth} header={<h1 className='h1'>Import Node from 'convoy/nodes'</h1>}>
      <Head title={`Import Node`} />

      <Main>
        <Link
          className='flex items-center space-x-2 p-desc'
          href={route('admin.nodes')}
        >
          <ArrowLeftIcon className='w-3 h-3' /> <span>Back</span>
        </Link>
        <h3 className='h3-emphasized'>Import a node.</h3>
        <p className='p-desc'>
          Please follow the steps to configure your node and create it.
        </p>
        <div className='flex flex-col !mt-9 space-y-3'>
          <Paper shadow='xs' className='p-card w-full relative overflow-hidden'>
            <h3 className='h3'>Configure Node</h3>
            <Alert className='mt-3' icon={<ExclamationCircleIcon className='h-5 w-5' />} title='Warning' color='yellow' variant='filled'>
              Disable 'privilege separation' when creating the API Token in Proxmox Virtual Environment
            </Alert>
            <form className='mt-3 space-y-3' onSubmit={submit}>
              <TextInput
                label='Display Name'
                name='name'
                value={data.name}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.name}
                required
              />
              <div className='grid sm:grid-cols-7 sm:gap-3'>
                <TextInput
                  label='Hostname'
                  name='hostname'
                  value={data.hostname}
                  className='mt-1 block w-full sm:col-span-6'
                  onChange={onHandleChange}
                  error={errors.hostname}
                  required
                />
                <NumberInput
                  label='Port'
                  name='port'
                  value={data.port}
                  className='mt-1 block w-full sm:col-span-1'
                  onChange={(e) => setData('port', e as number)}
                  error={errors.port}
                  required
                />
              </div>
              <TextInput
                label='Node Name (as appears under Nodes in Proxmox)'
                name='cluster'
                value={data.cluster}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.cluster}
                required
              />
              <TextInput
                label='Token ID'
                name='token_id'
                value={data.token_id}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.token_id}
                required
              />
              <TextInput
                label='Token Secret'
                name='secret'
                type='password'
                value={data.secret}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.secret}
                required
              />

              <Button
                className='!mt-9'
                type='submit'
                loading={processing}
                fullWidth
              >
                Deploy
              </Button>
            </form>
          </Paper>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Create
