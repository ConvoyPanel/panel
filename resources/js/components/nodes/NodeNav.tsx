import SubNavigation from '@/components/elements/SubNavigation'
import { Inertia } from '@inertiajs/inertia'
import { Tabs, TabsValue } from '@mantine/core'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const NodeNav = ({ id }: Props) => {
  const routes = [
    {name: 'Overview', link: 'admin.nodes.show'},
    {name: 'Addresses', link: 'admin.nodes.show.addresses'},
    {name: 'Settings', link: 'admin.nodes.show.settings'},
  ]

  return ( <SubNavigation id={id} routes={routes} /> )
}

export default NodeNav
