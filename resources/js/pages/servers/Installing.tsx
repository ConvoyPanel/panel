import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head } from '@inertiajs/inertia-react'
import { Paper } from '@mantine/core'
import LoadingState from '@/components/LoadingState'
import { useEffect } from 'react'
import { Inertia } from '@inertiajs/inertia'

interface Props extends DefaultProps {
  server: Server
}

const Installing = ({ auth, server }: Props) => {
  useEffect(() => {
    const refreshInterval = setInterval(() => {
      Inertia.reload()
    }, 3000)

    return () => {
      clearInterval(refreshInterval)
    }
  }, [])
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
    >
      <Head title={`${server.name} - Installing`} />

      <Main>
        <h3 className='h3-deemphasized'>Server Installing</h3>
        <Paper shadow='xs' className='p-card'>
          <LoadingState title='Rebuilding Server' />
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Installing
