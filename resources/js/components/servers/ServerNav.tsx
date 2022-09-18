import getActivity from '@/api/server/getActivity'
import { Inertia } from '@inertiajs/inertia'
import { Tabs, TabsValue } from '@mantine/core'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const ServerNav = ({ id }: Props) => {
  const routes = [
    'servers.show',
    'servers.show.snapshots',
    'servers.show.backups',
    'servers.show.logs',
    'servers.show.security',
    'servers.show.settings',
  ]

  const [active, setActive] = useState<string | null>(
    route().current() as string
  )
  const onChange = (value: TabsValue) => {
    setActive(value)
    Inertia.visit(route(value!, id))
  }

  useEffect(() => {
    setActive(route().current() as string)

    window.Echo.private(`server.${id}`).listen('ActivityLogged', (e: any) => {
      console.log(e)
    })

    return () => {
      window.Echo.leave(`server.${id}`)
    }
  }, [])

  return (
    <div className='bg-gray-50 border-y border-gray-200'>
      <div className='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
        <Tabs
          value={active}
          onTabChange={onChange}
          styles={{
            root: {
              overflowX: 'auto',
              '&::-webkit-scrollbar': { display: 'none' },
              scrollbarWidth: 'none',
            },
            tabsList: {
              flexWrap: 'nowrap',
              borderBottom: '2px solid transparent',
            },
          }}
        >
          <Tabs.List>
            <Tabs.Tab value={routes[0]}>Overview</Tabs.Tab>
            <Tabs.Tab value={routes[1]}>Snapshots</Tabs.Tab>
            <Tabs.Tab value={routes[2]}>Backups</Tabs.Tab>
            <Tabs.Tab value={routes[3]}>Logs</Tabs.Tab>
            <Tabs.Tab value={routes[4]}>Security</Tabs.Tab>
            <Tabs.Tab value={routes[5]}>Settings</Tabs.Tab>
          </Tabs.List>
        </Tabs>
      </div>
    </div>
  )
}

export default ServerNav
