import { CloudinitConfig } from '@/api/server/settings/types'
import getCredentials, { VncCredentials } from '@/api/server/vnc/getCredentials'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import PasswordConfigSettings from '@/pages/servers/security/modules/PasswordConfigSettings'
import { SettingsContext } from '@/pages/servers/settings/Index'
import { Head } from '@inertiajs/inertia-react'
import { Loader } from '@mantine/core'
import { useEffect, useMemo, useState } from 'react'
import { VncScreen } from 'react-vnc'

interface Props extends DefaultProps {
  server: Server
}

const Index = ({ auth, server }: Props) => {
  const [credentials, setCredentials] = useState<VncCredentials | undefined>()
  useEffect(() => {
    const main = async () => {
      const { data } = await getCredentials(server.id)

      setCredentials(data)

      console.log(data.endpoint)
    }
    main()
  }, [])

  const endpoint = useMemo(( ) => {
    if (!credentials) return ''

    const link = credentials.endpoint.replace(/^http/, 'ws').replace(/^https/, 'wss').slice(0, -1)
    const url = `${link}?port=${encodeURIComponent(credentials.port)}&vncticket=${encodeURIComponent(credentials.ticket)}`

    return url
  }, [credentials])

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - NoVNC`} />

      <Main>
        <h3 className='h3-deemphasized'>NoVNC Terminal</h3>
        {credentials && <VncScreen
        url={endpoint}
        scaleViewport
        background="#000000"
        style={{
          width: '75vw',
          height: '75vh',
        }}
        />}
        {!credentials && <div className='grid place-items-center w-full h-[30vh]'>
            <div className='flex flex-col items-center space-y-3'>
                <Loader />
                <h3 className='h3-deemphasized'>Connecting</h3>
            </div>
        </div>}
      </Main>
    </Authenticated>
  )
}

export default Index
