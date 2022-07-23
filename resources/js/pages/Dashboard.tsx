import React, { useMemo } from 'react'
import Authenticated from '@/components/layouts/Authenticated'
import { Head } from '@inertiajs/inertia-react'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Main from '@/components/Main'
import ProjectCards from '@/components/ProjectCards'

interface Props extends DefaultProps {
  servers: Server[]
}

export default function Dashboard({ auth, servers }: Props) {
  const formattedServers = useMemo(() => servers.map((server) => {
    return {
        name: server.name,
        description: server.description,
        link: route('servers.show', server.id),
    }
  }), [servers])

  return (
    <Authenticated
      auth={auth}
      header={
        <h1 className='h1'>
          Dashboard
        </h1>
      }
    >
      <Head title='Dashboard' />

      <Main>
        <h2 className='h3-deemphasized'>Your Servers</h2>
        <ProjectCards projects={formattedServers} />
      </Main>
    </Authenticated>
  )
}
