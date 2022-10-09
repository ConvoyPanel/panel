import SubNavigation from '@/components/elements/SubNavigation'

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
