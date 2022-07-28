import { Node } from '@/api/admin/nodes/types'
import { Template } from '@/api/admin/servers/templates/types'
import { Server as DefaultServer } from '@/api/server/types'
import { AuthInterface, DefaultProps } from '@/api/types/default'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import NodeNav from '@/components/nodes/NodeNav'
import ServerNav from '@/components/servers/admin/ServerNav'
import BasicSettings from '@/pages/admin/servers/settings/modules/BasicSettings'
import { Head } from '@inertiajs/inertia-react'
import { createContext } from 'react'

interface Props extends DefaultProps {
  server: Server
}

interface Server extends DefaultServer {
  template?: Template
}

export interface SettingsContextInterface {
  server: Server
  auth: AuthInterface
}

export const SettingsContext = createContext<SettingsContextInterface | null>(
  null
)

const Index = ({ auth, server }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Settings`} />

      <Main>
        <h3 className='h3-deemphasized'>Settings</h3>
        <SettingsContext.Provider value={{ server, auth }}>
          <div className='settings-grid'>
            <div className='settings-column'>
              <BasicSettings />
            </div>
            <div className='settings-column'>
            </div>
          </div>
        </SettingsContext.Provider>
      </Main>
    </Authenticated>
  )
}

export default Index
