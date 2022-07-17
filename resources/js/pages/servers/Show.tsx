import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/types/server'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerStatistics from '@/components/ServerStatistics'
import useServerState from '@/util/useServerState'
import { Head } from '@inertiajs/inertia-react'
import { useEffect, useRef } from 'react'

interface Props extends DefaultProps {
    server: Server
}

const Show = ({ auth, server}: Props) => {
    const { serverState, updateServerStatus } = useServerState(server.id)

    useEffect(() => {
      updateServerStatus()

      const updateStateInterval = setInterval(updateServerStatus, 1000)

      return () => {
        clearInterval(updateStateInterval)
      }
    }, [])

    useEffect(() => {
      console.log(serverState)
    }, [serverState])

    return <Authenticated
      auth={auth}
      header={
        <h1 className='h1'>
          { server.name }
        </h1>
      }
    >
      <Head title={`${server.name} — Overview`} />

      <Main>
        <ServerStatistics id={server.id} />
      </Main>
    </Authenticated>
}

export default Show