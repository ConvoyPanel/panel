import { colorState } from '@/api/server/getStatus'
import { ServerContext } from '@/pages/servers/Show'
import classNames from '@/util/classNames'
import useServerState from '@/util/useServerState'
import { useContext, useMemo } from 'react'

const ServerStatistics = () => {
  const serverContext = useContext(ServerContext)
  const { serverState } = useServerState(serverContext?.server.id as number)

  interface Statistic {
    name: string
    stat: number | string
    substat?: number | string
    caption?: number | string
    color?: 'danger' | 'success' | 'warning' | 'neutral'
  }

  // capitalize first letter in word
  const capitalize = (word: string) => {
    return word.charAt(0).toUpperCase() + word.slice(1)
  }

  const stats = useMemo<Statistic[]>(
    () => [
      {
        name: 'CPU',
        stat: `${serverState?.cpu || 0}%`,
        caption: capitalize(serverState?.state || '') || 'Connecting...',
        color: colorState[
          serverState?.state || 'querying'
        ] as Statistic['color'],
      },
      {
        name: 'Memory',
        stat: `${serverState?.mem.size || 0} ${serverState?.mem.unit || 'B'}`,
        substat: `/ ${serverState?.maxmem.size || 0} ${
          serverState?.maxmem.unit || 'B'
        }`,
      },
      {
        name: 'Uptime',
        stat: serverState ? Math.trunc(serverState.uptime.time) : 'Offline',
        substat: serverState ? serverState.uptime.unit : '',
      },
    ],
    [serverState]
  )

  return (
    <div>
      <h3 className='h3-deemphasized'>Live Server Statistics</h3>
      <dl className='mt-3 grid grid-cols-1 rounded bg-white overflow-hidden shadow divide-y divide-gray-200 md:grid-cols-3 md:divide-y-0 md:divide-x'>
        {stats.map((item) => (
          <div key={item.name} className='px-4 py-5 sm:p-6'>
            <dt className='text-base font-normal text-gray-900'>{item.name}</dt>
            <dd className='mt-1 flex justify-between items-baseline md:block lg:flex'>
              <div className='flex items-baseline text-2xl font-semibold text-blue-600'>
                {item.stat}
                {item.substat && (
                  <span className='ml-2 text-sm font-medium text-gray-500'>
                    {item.substat}
                  </span>
                )}
              </div>

              {item.caption && (
                <div
                  className={classNames(
                    item.color === 'success'
                      ? 'bg-green-100 text-green-800'
                      : '',
                    item.color === 'danger' ? 'bg-red-100 text-red-800' : '',
                    item.color === 'warning'
                      ? 'bg-yellow-100 text-yellow-800'
                      : '',
                    item.color === 'neutral' ? 'bg-gray-100 text-gray-800' : '',
                    'inline-flex items-baseline px-2.5 py-0.5 rounded-full text-sm font-medium md:mt-2 lg:mt-0'
                  )}
                >
                  {item.caption}
                </div>
              )}
            </dd>
          </div>
        ))}
      </dl>
    </div>
  )
}

export default ServerStatistics
