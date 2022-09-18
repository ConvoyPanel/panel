import { Inertia } from '@inertiajs/inertia'
import { Tabs, TabsValue } from '@mantine/core'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const NodeNav = ({ id }: Props) => {
  const routes = [
    'admin.nodes.show',
    'admin.nodes.show.addresses',
    'admin.nodes.show.settings',
  ]

  const [active, setActive] = useState<string | null>(route().current() as string)
  const onChange = (value: TabsValue) => {
    setActive(value)
    Inertia.visit(route(value!, id))
  }

  useEffect(() => {
    setActive(route().current() as string)
  }, [])

  return (
    <div className='bg-gray-50 border-y border-gray-200'>
      <div className='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
        <Tabs
          value={active}
          onTabChange={onChange}
          styles={{
            /* tabsListWrapper: {
              borderStyle: 'none !important',
            }, */
            tabsList: {
              flexWrap: 'nowrap',
              overflowX: 'auto',
              '&::-webkit-scrollbar': { display: 'none' },
              scrollbarWidth: 'none',
            },
          }}
        >
          <Tabs.Tab value={routes[0]}>Overview</Tabs.Tab>
          <Tabs.Tab value={routes[1]}>Addresses</Tabs.Tab>
          <Tabs.Tab value={routes[2]}>Settings</Tabs.Tab>
        </Tabs>
      </div>
    </div>
  )
}

export default NodeNav
