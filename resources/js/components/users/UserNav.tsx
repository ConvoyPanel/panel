import SubNavigation from '@/components/elements/SubNavigation'
import { Inertia } from '@inertiajs/inertia'
import { Tabs } from '@mantine/core'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const UserNav = ({ id }: Props) => {
  const routes = [
    { name: 'Overview', link: 'admin.users.show' },
    { name: 'Settings', link: 'admin.users.show.settings' },
  ]

  return <SubNavigation id={id} routes={routes} />
}

export default UserNav
