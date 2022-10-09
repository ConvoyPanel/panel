import { CloudinitConfig } from '@/api/server/settings/types'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import PasswordConfigSettings from '@/pages/servers/security/modules/PasswordConfigSettings'
import VncRedirectSettings from '@/pages/servers/security/modules/VncRedirectSettings'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { Head } from '@inertiajs/inertia-react'

interface Props extends DefaultProps {
  server: Server
  config: CloudinitConfig
}

const Index = ({ auth, server, config }: Props) => {
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Security`} />

      <Main>
        <h3 className='h3-deemphasized'>Security Settings</h3>
        <div className='settings-grid'>
          <SettingsContext.Provider value={{ server, config, auth }}>
            <div className='settings-column'>
              <PasswordConfigSettings />
            </div>
            <div className='settings-column'>
              <VncRedirectSettings />
            </div>
          </SettingsContext.Provider>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
