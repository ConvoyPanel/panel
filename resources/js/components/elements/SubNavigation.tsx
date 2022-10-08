import { Inertia } from '@inertiajs/inertia'
import { Tabs, TabsValue } from '@mantine/core'
import { useEffect, useState } from 'react'

export interface Route {
  name: string
  link: string
}

export interface Props {
  id: string | number
  routes: Route[]
}

const SubNavigation = ({ id, routes }: Props) => {
  const [active, setActive] = useState<string | null>(
    route().current() as string
  )
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
            {routes.map((route, index) => (
              <Tabs.Tab key={index} value={route.link}>
                {route.name}
              </Tabs.Tab>
            ))}
          </Tabs.List>
        </Tabs>
      </div>
    </div>
  )
}

export default SubNavigation
