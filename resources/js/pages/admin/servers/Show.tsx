import { Node } from '@/api/admin/nodes/types'
import { Server } from '@/api/server/types'
import { DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import ServerNav from '@/components/servers/admin/ServerNav'
import { Head } from '@inertiajs/inertia-react'
import { Button, Paper } from '@mantine/core'

interface Props extends DefaultProps {
    server: Server
}

const Show = ({ auth, server }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{ server.name }</h1>}
      secondaryHeader={<ServerNav id={server.uuidShort} />}
    >
      <Head title={`${server.name} - Overview`} />

      <Main>
        <h3 className='h3-deemphasized'>Overview</h3>
        <Paper shadow='xs' className='p-card'>
          <Button href={route('servers.show', server.uuidShort)} target='_blank' component='a'>Enter server management</Button>
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Show
