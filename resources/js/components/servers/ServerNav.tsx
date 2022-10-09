import SubNavigation from '@/components/elements/SubNavigation'
import { useEffect, useState } from 'react'

interface Props {
  id: number
}

const ServerNav = ({ id }: Props) => {
  const routes = [
    {name: 'Overview', link: 'servers.show'},
    {name: 'Snapshots', link: 'servers.show.snapshots'},
    {name: 'Backups', link: 'servers.show.backups'},
    {name: 'Logs' , link: 'servers.show.logs'},
    {name: 'Security', link: 'servers.show.security'},
    {name: 'Settings', link: 'servers.show.settings'},
  ]

  useEffect(() => {
    window.Echo.private(`server.${id}`).listen('ActivityLogged', (e: any) => {
      console.log(e)
    })

    return () => {
      window.Echo.leave(`server.${id}`)
    }
  }, [])

  return (
    <SubNavigation id={id} routes={routes} />
  )
}

export default ServerNav
