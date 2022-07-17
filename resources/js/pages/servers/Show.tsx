import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
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

interface Props extends DefaultProps {
  server: Server
}

export interface ServerContextInterface {
  server: Server
}

export const ServerContext = createContext<ServerContextInterface | null>(null)

const Show = ({ auth, server }: Props) => {
  const { serverState, updateServerStatus } = useServerState(server.id)

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
      header={<h1 className='h1'>{server.name}</h1>}
      secondaryHeader={
        <div className='bg-gray-50 border-y border-gray-200'>
          <div className='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
            <Tabs styles={{
              tabsListWrapper: {
                borderStyle: 'none !important',
              },
              tabsList: {
                flexWrap: 'nowrap',
                overflowX: 'auto',
                '&::-webkit-scrollbar': { display: 'none' },
                scrollbarWidth: 'none',
              }
             }}>
              <Tabs.Tab label='Overview'></Tabs.Tab>
              <Tabs.Tab label='Snapshots'></Tabs.Tab>
              <Tabs.Tab label='Backups'></Tabs.Tab>
              <Tabs.Tab label='Security'></Tabs.Tab>
              <Tabs.Tab label='Settings'></Tabs.Tab>
            </Tabs>
          </div>
        </div>
      }
    >
      <Head title={`${server.name} - Overview`} />

      <Main>
        <ServerContext.Provider value={{ server }}>
          <ServerStatistics />

          <PowerActions />

          {serverState ? <StatGraphs /> : <Loader />}
        </ServerContext.Provider>
      </Main>
    </Authenticated>
  )
}

export default Show
