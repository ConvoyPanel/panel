import getLogs, { Logs } from '@/api/server/logs/getLogs'
import { DefaultProps } from '@/api/types/default'
import { Server } from '@/api/server/types'
import Authenticated from '@/components/layouts/Authenticated'
import Main from '@/components/Main'
import ServerNav from '@/components/servers/ServerNav'
import classNames from '@/util/classNames'
import dateTimeCalculator from '@/util/dateTimeCalculator'
import { CheckIcon } from '@heroicons/react/solid'
import { Head } from '@inertiajs/inertia-react'
import { Paper, Table } from '@mantine/core'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import LoadingState from '@/components/LoadingState'

interface Props extends DefaultProps {
  server: Server
}

const Index = ({ auth, server }: Props) => {
  const { data, status } = useQuery<Logs>(['logs'], async () => {
    const { data } = await getLogs(server.id)
    return data
  })

  const calculateTime = (time: number) => {
    const { hours, minutes, month, day, year } = dateTimeCalculator(time)
    // add missing zero in minutes
    const minutesString = minutes < 10 ? `0${minutes}` : minutes
    return `${hours}:${minutesString} ${month} ${day}, ${year}`
  }

  return (
    <Authenticated
      auth={auth}
      header={<h1 className='server-title'>{server.name}</h1>}
      secondaryHeader={<ServerNav id={server.id} />}
    >
      <Head title={`${server.name} - Logs`} />

      <Main>
        <h3 className='h3-deemphasized'>Logs</h3>
        <Paper shadow='xs' className='p-card w-full'>
          <Table striped highlightOnHover>
            <thead>
              <tr>
                <th>Action</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {data &&
                data.data?.map((log) => (
                  <>
                    <tr
                      className={classNames(
                        log.status !== 'OK'
                          ? '!bg-red-100 hover:!bg-red-200'
                          : ''
                      )}
                      key={log.upid}
                    >
                      <td>{log.type}</td>
                      <td>{calculateTime(log.starttime)}</td>
                      <td>{calculateTime(log.endtime)}</td>
                      <td>
                        {log.status === 'OK' ? (
                          <CheckIcon className='text-green-600 w-[18px] h-[18px]' />
                        ) : (
                          log.status
                        )}
                      </td>
                      <td></td>
                    </tr>
                  </>
                ))}
            </tbody>
          </Table>

          {status === 'loading' && <LoadingState title='Fetching logs' />}
        </Paper>
      </Main>
    </Authenticated>
  )
}

export default Index
