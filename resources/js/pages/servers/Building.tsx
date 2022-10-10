import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import { Head } from '@inertiajs/inertia-react'
import { Accordion, Code, Group, Loader, Paper } from '@mantine/core'
import LoadingState from '@/components/LoadingState'
import { Fragment, useEffect, useRef, useState } from 'react'
import { Inertia } from '@inertiajs/inertia'
import { XCircleIcon, XIcon } from '@heroicons/react/outline'
import { Activity } from '@/api/server/getActivity'
import { CheckCircleIcon } from '@heroicons/react/solid'

interface Props extends DefaultProps {
  server: Server
  batch?: string
  batch_type?: 'server:rebuild' | 'server:build'
  events: Activity[]
}

interface AccordionProps {
  name: string
  timeStart?: Date
  timeEnd?: Date
}

const AccordionLabel = ({ name, timeStart, timeEnd }: AccordionProps) => {
  const [timeElapsed, setTimeElapsed] = useState(0)

  const interval = useRef<number>()

  useEffect(() => {
    if (timeStart && !timeEnd && !interval.current) {
      interval.current = window.setInterval(() => {
        // set time elapsed to number of seconds
        setTimeElapsed(
          parseFloat(((Date.now() - timeStart.getTime()) / 1000).toFixed(1))
        )
      }, 100)
    }

    if (timeStart && timeEnd) {
      setTimeElapsed(
        parseFloat(
          ((timeEnd.getTime() - timeStart.getTime()) / 1000).toFixed(1)
        )
      )
      window.clearInterval(interval.current)
      interval.current = undefined
    }
  }, [timeStart, timeEnd])

  // add extra .0 to time elapsed if it is a whole number
  const timeElapsedString =
    timeElapsed % 1 === 0 ? `${timeElapsed}.0` : timeElapsed

  return (
    <div className='flex justify-between items-center p-1'>
      <p>{name}</p>
      <div className='flex items-center space-x-2'>
        {timeStart && <p className='p-deemphasized'>{timeElapsedString}s</p>}
        {!timeStart && !timeEnd ? (
          <svg
            className='h-6 w-6 stroke-gray-500'
            viewBox='0 0 100 100'
            xmlns='http://www.w3.org/2000/svg'
          >
            <circle cx='50' cy='50' r='37' strokeWidth='6' fill='none' />
          </svg>
        ) : (
          ''
        )}
        {timeStart && !timeEnd ? <Loader size='sm' /> : ''}
        {timeEnd && <CheckCircleIcon className='text-blue-500 w-6 h-6' />}
      </div>
    </div>
  )
}

interface ActivityEvent {
  data: Activity
}

interface Events {
  [key: string]: {
    data?: Activity
    title: string
    description: string
  }
}

const Building = ({
  auth,
  server,
  batch,
  batch_type,
  events: hydratedEvents,
}: Props) => {
  const [events, setEvents] = useState<Events>({
    'server:uninstall': {
      title: 'Uninstalling Server',
      description: 'This server is currently being uninstalled.',
    },
    'server:install': {
      title: 'Copying Context',
      description: 'This server is currently being installed.',
    },
    'server:details.update': {
      title: 'Updating Server Details',
      description: 'This server is currently updating its details.',
    },
  })

  const s = new Date()
  s.setMinutes(s.getMinutes() + 5)

  useEffect(() => {
    const refreshInterval = setInterval(() => {
      Inertia.reload()
    }, 5000)

    window.Echo.private(`server.${server.id}`)
      .listen('Activity\\ActivityLogged', (event: ActivityEvent) =>
        updateActivity(event.data)
      )
      .listen('Activity\\ActivityUpdated', (event: ActivityEvent) =>
        updateActivity(event.data)
      )

    if (batch && batch_type) {
      hydratedEvents.forEach((event) => {
        updateActivity(event)
      })
    }

    return () => {
      clearInterval(refreshInterval)
      window.Echo.leave(`server.${server.id}`)
    }
  }, [])

  const updateActivity = (activity: Activity) => {
    if (events[activity.event] === undefined || activity.batch !== batch) return

    setEvents((events) => ({
      ...events,
      [activity.event]: {
        ...events[activity.event],
        data: activity,
      },
    }))
  }

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
            <>
              <Code>{batch}</Code>
              <div className='rounded-md overflow-hidden mt-3'>
                <Accordion chevronPosition='left'>
                  {Object.keys(events).map((event) => (
                    <Fragment key={event}>
                      {(event === 'server:rebuild' && event === batch_type) ||
                      (event === 'server:build' && event === batch_type) ||
                      (event !== 'server:build' &&
                        event !== 'server:rebuild') ? (
                        <Accordion.Item value={event}>
                          <Accordion.Control
                            disabled={events[event].data === undefined}
                          >
                            <AccordionLabel
                              name={events[event].title}
                              timeStart={
                                events[event].data
                                  ? new Date(events[event].data!.created_at)
                                  : undefined
                              }
                              timeEnd={
                                events[event]?.data?.status === 'ok' &&
                                events[event]?.data?.updated_at
                                  ? new Date(events[event].data!.updated_at)
                                  : undefined
                              }
                            />
                          </Accordion.Control>
                          <Accordion.Panel>
                            {events[event].description}
                          </Accordion.Panel>
                        </Accordion.Item>
                      ) : (
                        ''
                      )}
                    </Fragment>
                  ))}
                </Accordion>
              </div>
            </>
          ) : (
            ''
          )}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Building
