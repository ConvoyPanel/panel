import { Node } from '@/api/admin/nodes/types'
import { Server } from '@/api/server/types'
import { DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import ServerNav from '@/components/servers/admin/ServerNav'
import { Head } from '@inertiajs/inertia-react'

interface Props extends DefaultProps {
    server: Server
}

const Show = ({ auth, server }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{ server.name }</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Overview`} />

      <Main>
      </Main>
    </Authenticated>
  )
}

export default Show
