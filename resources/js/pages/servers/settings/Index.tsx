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
import ReinstallSettings from '@/pages/servers/settings/modules/ReinstallSettings'

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
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Settings`} />

      <Main>
        <h3 className='h3-deemphasized'>Settings</h3>
        <div className='grid sm:grid-cols-2 md:grid-cols-3 gap-3 w-full'>
          <SettingsContext.Provider value={{ server, config, auth }}>
            <BasicSettings />
            <BiosConfigSettings />
            <NetworkConfigSettings />
            <ReinstallSettings />
          </SettingsContext.Provider>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
