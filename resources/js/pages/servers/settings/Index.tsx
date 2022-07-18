import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import BasicSettings from '@/pages/servers/settings/modules/BasicSettings'
import BiosConfigSettings from '@/pages/servers/settings/modules/BiosConfigSettings'
import { ServerContext } from '@/pages/servers/Show'
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
      <Head title={`${server.name} - Settings`} />

      <Main>
        <div className='grid sm:grid-cols-2 md:grid-cols-3 gap-3 w-full'>
          <ServerContext.Provider value={{ server }}>
            <BasicSettings />
            <BiosConfigSettings />
          </ServerContext.Provider>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
