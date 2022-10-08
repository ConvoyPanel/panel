import { CloudinitConfig } from '@/api/server/settings/types'
import { AuthInterface, DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import BasicSettings from '@/pages/servers/settings/modules/BasicSettings'
import BiosConfigSettings from '@/pages/servers/settings/modules/BiosConfigSettings'
import NetworkConfigSettings from '@/pages/servers/settings/modules/NetworkConfigSettings'
import { Head } from '@inertiajs/inertia-react'
import { createContext } from 'react'
import RebuildSettings from '@/pages/servers/settings/modules/RebuildSettings'

interface Props extends DefaultProps {
  server: Server
  config: CloudinitConfig
}

export interface SettingsContextInterface {
  server: Server
  config: CloudinitConfig
  auth: AuthInterface
}

export const SettingsContext = createContext<SettingsContextInterface | null>(
  null
)

const Index = ({ auth, server, config }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.uuidShort} />}
    >
      <Head title={`${server.name} - Settings`} />

      <Main>
        <h3 className='h3-deemphasized'>Settings</h3>
        <div className='settings-grid'>
          <SettingsContext.Provider value={{ server, config, auth }}>
            <div className='settings-column'>
              <BasicSettings />
              <RebuildSettings />
            </div>
            <div className='settings-column'>
              <BiosConfigSettings />
            </div>
            <div className='settings-column'>
              <NetworkConfigSettings />
            </div>
          </SettingsContext.Provider>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
