import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head } from '@inertiajs/inertia-react'
import { Accordion, Group, Loader, Paper } from '@mantine/core'
import LoadingState from '@/components/LoadingState'
import { useEffect, useRef, useState } from 'react'
import { Inertia } from '@inertiajs/inertia'
import { XCircleIcon, XIcon } from '@heroicons/react/outline'

interface Props extends DefaultProps {
  server: Server
  batch?: string
  batch_type?: string
}

interface AccordionProps {
  name: string
  timeStart?: Date
  timeEnd?: Date
}

const AccordionLabel = ({name, timeStart, timeEnd}: AccordionProps) => {
  const [timeElapsed, setTimeElapsed] = useState(0)

  const interval = useRef<number>()

  useEffect(() => {
    if (timeStart && !timeEnd && !interval.current) {
      interval.current = window.setInterval(() => {
        // set time elapsed to number of seconds
        setTimeElapsed(parseFloat(((Date.now() - timeStart.getTime()) / 1000).toFixed(1)))
      }, 100)
    }

    if (timeEnd) {
      window.clearInterval(interval.current)
      interval.current = undefined
    }

  }, [timeStart, timeEnd])

  // add extra .0 to time elapsed if it is a whole number
  const timeElapsedString = timeElapsed % 1 === 0 ? `${timeElapsed}.0` : timeElapsed

  return (
    <div className='flex justify-between items-center p-1'>
      <p>{name}</p>
      <div className='flex items-center space-x-2'>
        <p className='p-deemphasized'>{timeElapsedString}s</p>
        {(timeStart && !timeEnd) ?
        <Loader size='xs' /> : ''}
      </div>
    </div>
  )
}

const Building = ({ auth, server, batch, batch_type }: Props) => {
  //const [deployment, setDeployment] =
  useEffect(() => {
    const refreshInterval = setInterval(() => {
      Inertia.reload()
    }, 5000)

    window.Echo.private(`server.${server.id}`).listen(
      'Activity\\ActivityLogged',
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
          {!batch_type || !batch ? (
            <div className='grid place-items-center w-full h-[30vh]'>
              <div className='flex flex-col items-center space-y-3'>
                <XCircleIcon className='text-red-600 w-14 h-14' />
                <h3 className='h3-deemphasized'>Deployment Missing</h3>
              </div>
            </div>
          ) : (
            ''
          )}
          {batch_type && batch ? (
            <div className='rounded-md overflow-hidden mt-3'>
              <Accordion chevronPosition='left'>
                <Accordion.Item value='formatting'>
                  <Accordion.Control>
                    <AccordionLabel name='Formatting Server' timeStart={new Date()} />
                  </Accordion.Control>
                  <Accordion.Panel>
                    Convoy is clearing the server and formatting it for deployment.
                  </Accordion.Panel>
                </Accordion.Item>
                <Accordion.Item value='copying'>
                  <Accordion.Control disabled>
                    <AccordionLabel name='Copying Context' />
                  </Accordion.Control>
                  <Accordion.Panel>
                    Convoy is installing the specified template to your server. This may take a few minutes.
                  </Accordion.Panel>
                </Accordion.Item>
                <Accordion.Item value='updating'>
                  <Accordion.Control disabled>
                    <AccordionLabel name='Updating Server Configuration'/>
                  </Accordion.Control>
                  <Accordion.Panel>
                    Convoy is updating your server configurations.
                  </Accordion.Panel>
                </Accordion.Item>
              </Accordion>
            </div>
          ) : (
            ''
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Building
