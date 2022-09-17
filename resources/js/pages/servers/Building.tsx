import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head } from '@inertiajs/inertia-react'
import { Accordion, Paper } from '@mantine/core'
import LoadingState from '@/components/LoadingState'
import { useEffect, useState } from 'react'
import { Inertia } from '@inertiajs/inertia'
import getActivity from '@/api/server/getActivity'
import { XCircleIcon, XIcon } from '@heroicons/react/outline'

interface Props extends DefaultProps {
  server: Server
}

const Building = ({ auth, server }: Props) => {
  const [type, setType] = useState<string>()
  const [error, setError] = useState(false)
  const [batch, setBatch] = useState<string>()

  useEffect(() => {
    const refreshInterval = setInterval(() => {
      Inertia.reload()
    }, 5000)

    const main = async () => {
      let { data: rebuildData } = await getActivity(
        { event: 'server:rebuild', status: 'running' },
        server.id
      )

      if (rebuildData.meta.pagination.total !== 0) {
        setType('rebuild')
        setBatch(rebuildData.data[0].batch)
        return
      }

      let { data: buildData } = await getActivity(
        { event: 'server:build', status: 'running' },
        server.id
      )

      if (buildData.meta.pagination.total !== 0) {
        setType('build')
        setBatch(buildData.data[0].batch)
        return
      }

      setError(true)
    }

    main()

    window.Echo.private(`server.${server.id}`).listen(
      'ActivityLogged',
      (e: any) => {
        console.log(e)
      }
    )

    return () => {
      clearInterval(refreshInterval)
      window.Echo.leave(`server.${server.id}`)
    }
  }, [])
  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
    >
      <Head title={`${server.name} - Installing`} />

      <Main>
        <h3 className='h3-deemphasized'>Server Building</h3>
        <Paper shadow='xs' className='p-card'>
          <h1 className='h1'>Deployment Status</h1>
          {type === undefined && <LoadingState title='Fetching Deployment' />}
          {error && (
            <div className='grid place-items-center w-full h-[30vh]'>
              <div className='flex flex-col items-center space-y-3'>
                <XCircleIcon className='text-red-600 w-14 h-14' />
                <h3 className='h3-deemphasized'>Deployment Missing</h3>
              </div>
            </div>
          )}
          {type !== undefined && (
            <>
              <Accordion iconPosition='left' className='mt-3'>
                <Accordion.Item label='Format Server (5s)'>
                  test
                </Accordion.Item>
                <Accordion.Item label='Install Template (waiting)'>
                  test
                </Accordion.Item>
                <Accordion.Item label='Update Configuration (waiting)'>
                  test
                </Accordion.Item>
              </Accordion>
            </>
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Building
