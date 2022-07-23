import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import { Head } from '@inertiajs/inertia-react'

interface Props extends DefaultProps {
  server: Server
}

const Index = ({ auth, server }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Backups`} />

      <Main>
        <h3 className='h3-deemphasized'>Backups</h3>
        <h1>
          Backups are unavailable because the feature hasn't been implemented
        </h1>
      </Main>
    </Authenticated>
  )
}

export default Index
