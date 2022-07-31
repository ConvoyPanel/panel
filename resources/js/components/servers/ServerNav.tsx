import { Inertia } from '@inertiajs/inertia'
import { Tabs } from '@mantine/core'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const ServerNav = ({ id }: Props) => {
  const routes = [
    'servers.show',
    'servers.snapshots',
    'servers.backups',
    'servers.logs',
    'servers.security',
    'servers.settings',
  ]

  const [active, setActive] = useState(routes.indexOf(route().current() as string))
  const onChange = (active: number, tabKey: string) => {
    setActive(routes.indexOf(tabKey))
    Inertia.visit(route(tabKey, id))
  }

  useEffect(() => {
    setActive(routes.indexOf(route().current() as string))
  }, [])

  return (
    <div className='bg-gray-50 border-y border-gray-200'>
      <div className='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
        <Tabs
          active={active}
          onTabChange={onChange}
          styles={{
            tabsListWrapper: {
              borderStyle: 'none !important',
            },
            tabsList: {
              flexWrap: 'nowrap',
              overflowX: 'auto',
              '&::-webkit-scrollbar': { display: 'none' },
              scrollbarWidth: 'none',
            },
          }}
        >
          <Tabs.Tab label='Overview' tabKey={routes[0]}></Tabs.Tab>
          <Tabs.Tab label='Snapshots' tabKey={routes[1]}></Tabs.Tab>
          <Tabs.Tab label='Backups' tabKey={routes[2]}></Tabs.Tab>
          <Tabs.Tab label='Logs' tabKey={routes[3]}></Tabs.Tab>
          <Tabs.Tab label='Security' tabKey={routes[4]}></Tabs.Tab>
          <Tabs.Tab label='Settings' tabKey={routes[5]}></Tabs.Tab>
        </Tabs>
      </div>
    </div>
  )
}

export default ServerNav
