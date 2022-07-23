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
        <div className='grid sm:grid-cols-2 md:grid-cols-3 gap-3 w-full'>
          <SettingsContext.Provider
            value={{ server, config }}
          >
            <PasswordConfigSettings />
            <VncRedirectSettings />
          </SettingsContext.Provider>
        </div>
      </Main>
    </Authenticated>
  )
}

export default Index
