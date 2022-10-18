import { Server } from '@/api/server/types'
import { DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { XCircleIcon } from '@heroicons/react/outline'
import { Head } from '@inertiajs/inertia-react'
import { Paper } from '@mantine/core'

interface Props extends DefaultProps {
  server: Server
}

const Suspended = ({ auth, server }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
    >
      <Head title={`${server.name} - Suspended`} />

      <Main>
        <h3 className='h3-deemphasized'>Server Suspended</h3>
        <Paper shadow='xs' className='p-card'>
          <div className='grid place-items-center w-full h-[30vh]'>
            <div className='flex flex-col items-center space-y-3'>
              <XCircleIcon className='text-red-600 w-14 h-14' />
              <h3 className='h3-deemphasized'>Server Suspended</h3>
            </div>
          </div>
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Suspended
