import SubNavigation from '@/components/elements/SubNavigation'
import { Inertia } from '@inertiajs/inertia'
import { Tabs } from '@mantine/core'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const ServerNav = ({ id }: Props) => {
  const routes = [
    { name: 'Overview', link: 'admin.servers.show' },
    { name: 'Settings', link: 'admin.servers.show.settings' },
  ]

  return <SubNavigation id={id} routes={routes} />
}

export default ServerNav
