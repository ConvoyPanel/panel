import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head, Link, useForm } from '@inertiajs/inertia-react'
import { ArrowLeftIcon, ChevronLeftIcon } from '@heroicons/react/solid'
import {
  Button,
  Code,
  NumberInput,
  Overlay,
  Paper,
  TextInput,
  ActionIcon,
  Tooltip,
} from '@mantine/core'
import { ChangeEvent, FormEvent, useState } from 'react'
import { formDataHandler } from '@/util/helpers'
import { CheckCircleIcon } from '@heroicons/react/outline'
import { Inertia } from '@inertiajs/inertia'

interface Props extends DefaultProps {
  server: Server
}

const VncCommands = `wget -qO- https://github.com/Performave/convoy-novnc/archive/refs/heads/main.zip | unzip`

const Create = ({ auth }: Props) => {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    cluster: '',
    hostname: '',
    username: '',
    password: '',
    port: 8006,
    auth_type: 'pam',
  })

  const [deployed, setDeployed] = useState(false)

  const onHandleChange = (event: ChangeEvent<HTMLInputElement>) =>
    formDataHandler(event, setData)

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    post(route('admin.nodes.store'), {
      onSuccess: () => {
        setDeployed(true)
      },
    })
  }

  return (
    <Authenticated auth={auth} header={<h1 className='h1'>Import Node from 'convoy/nodes'</h1>}>
      <Head title={`Import Node`} />

      <Main>
        <Link
          className='flex items-center space-x-2 p-desc'
          href={route('admin.nodes.index')}
        >
          <ArrowLeftIcon className='w-3 h-3' /> <span>Back</span>
        </Link>
        <h3 className='h3-emphasized'>Import a node.</h3>
        <p className='p-desc'>
          Please follow the steps to configure your node and create it.
        </p>
        <div className='flex flex-col !mt-9 space-y-3'>
          <Paper shadow='xs' className='p-card w-full relative overflow-hidden'>
            {deployed && (
              <>
                <div className='grid place-items-center absolute inset-0 z-[7]'>
                  <div className='flex flex-col items-center space-y-3'>
                    <CheckCircleIcon className='text-green-400 w-14 h-14' />
                    <h3 className='!text-white h3-deemphasized'>
                      Successfully Deployed
                    </h3>
                  </div>
                </div>
                <Overlay opacity={0.6} color='#000' zIndex={5}></Overlay>
              </>
            )}
            <h3 className='h3 '>Configure Node</h3>
            <form className='mt-3 space-y-3' onSubmit={submit}>
              <TextInput
                label='Display Name'
                name='name'
                value={data.name}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.name}
                disabled={deployed}
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
                  disabled={deployed}
                  required
                />
                <NumberInput
                  label='Port'
                  name='port'
                  value={data.port}
                  className='mt-1 block w-full sm:col-span-1'
                  onChange={(e) => setData('port', e as number)}
                  error={errors.port}
                  disabled={deployed}
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
                disabled={deployed}
                required
              />
              <TextInput
                label='Username'
                name='username'
                value={data.username}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.username}
                disabled={deployed}
                required
              />
              <TextInput
                label='Password'
                name='password'
                type='password'
                value={data.password}
                className='mt-1 block w-full'
                onChange={onHandleChange}
                error={errors.password}
                disabled={deployed}
                required
              />

              <Button
                className='!mt-9'
                type='submit'
                loading={processing}
                disabled={deployed}
                fullWidth
              >
                Deploy
              </Button>
            </form>
          </Paper>
          <Paper shadow='xs' className='p-card w-full space-y-3'>
            <h3 className='h3'>Configure VNC Service</h3>

            <p className='p-desc'>
              Install the noVNC broker to allow clients to connect their
              servers. Without this, clients will not be able to manage their
              servers from the web.{' '}
              <span className='font-bold'>
                Run this command on the node you're deploying on.
              </span>
            </p>
            <Code block>{VncCommands}</Code>
          </Paper>
          <Paper shadow='xs' className='p-card w-full space-y-3'>
            <h3 className='h3'>Add Server Templates</h3>

            <p className='p-desc'>
              Server templates are unavailable at the moment.
            </p>
          </Paper>

          <div className='grid place-items-center h-[30vh] w-full'>
            <div className='flex flex-col space-y-3 items-center'>
              <CheckCircleIcon className='text-green-600 w-14 h-14' />
              <h3 className='h3-deemphasized'>You're all set!</h3>
              <Button onClick={() => Inertia.visit(route('admin.nodes.index')) }>View All Nodes</Button>
            </div>
          </div>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Create
