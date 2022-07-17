import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import BasicSettings from '@/pages/servers/settings/modules/BasicSettings'
import { ServerContext } from '@/pages/servers/Show'
import { Head } from '@inertiajs/inertia-react'

interface Props extends DefaultProps {
  server: Server
}

const Index = ({ auth, server }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='h1'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Settings`} />

      <Main>
        <div className='grid md:grid-cols-4 w-full'>
        <ServerContext.Provider value={{ server }}>
          <BasicSettings />
          </ServerContext.Provider>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
