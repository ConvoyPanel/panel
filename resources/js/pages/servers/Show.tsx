import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import PowerActions from '@/components/servers/PowerActions'
import ServerStatistics from '@/components/servers/ServerStatistics'
import { ServerData } from '@/state/server'
import useServerState from '@/util/useServerState'
import { Head } from '@inertiajs/inertia-react'
import { createContext, useEffect } from 'react'
import StatGraphs from '@/components/servers/StatGraphs'
import { Loader, Tabs } from '@mantine/core'
import ServerNav from '@/components/servers/ServerNav'
import ServerUnavailableModal from '@/components/servers/ServerUnavailableModal'
import ServerResources from '@/components/servers/ServerResources'

interface Props extends DefaultProps {
  server: Server
}

export interface ServerContextInterface {
  server: Server
}

export const ServerContext = createContext<ServerContextInterface | null>(null)

const Show = ({ auth, server }: Props) => {
  const { serverState, updateServerStatus, isErroring } = useServerState(
    server.id
  )

  useEffect(() => {
    updateServerStatus()

    const updateStateInterval = setInterval(updateServerStatus, 1000)

    return () => {
      clearInterval(updateStateInterval)
    }
  }, [])

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Overview`} />

      <Main>
        <ServerContext.Provider value={{ server }}>
          <ServerUnavailableModal opened={isErroring} />

          <ServerStatistics />

          <PowerActions />

          <ServerResources />

          {serverState ? (
            <StatGraphs />
          ) : (
            <div className='grid place-items-center h-[30vh] w-full'>
              <div className='flex flex-col space-y-3 items-center'>
                <Loader />
                <h3 className='h3-deemphasized'>Connecting</h3>
              </div>
            </div>
          )}
        </ServerContext.Provider>
      </Main>
    </Authenticated>
  )
}

export default Show
