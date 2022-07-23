import { useQuery } from '@tanstack/react-query'
import getResources from '@/api/server/getResources'
import { ServerContext } from '@/pages/servers/Show'
import { useContext } from 'react'
import { Paper, Skeleton } from '@mantine/core'
import { formatBytes } from '@/api/server/getStatus'

const ServerResources = () => {
  const serverContext = useContext(ServerContext)

  const { data, status } = useQuery(['resources'], async () => {
    const { data } = await getResources(serverContext!.server.id)
    return data
  })

  const memory = formatBytes(data?.maxmem || 0)
  const disk = formatBytes(data?.maxdisk || 0)
  const diskWritten = formatBytes(data?.diskwrite || 0)
  const diskRead = formatBytes(data?.diskread || 0)
  const networkIn = formatBytes(data?.netin || 0)
  const networkOut = formatBytes(data?.netout || 0)

  return (
    <div>
      <h3 className='h3-deemphasized'>Specifications</h3>
      <Paper shadow='xs' className='p-card mt-3 flex flex-col space-y-3'>
        {status === 'success' && (
          <>
            <dl>
              <dt className='dt'>IP Address</dt>
              <dd className='dd'>{serverContext?.server.ip_address ? serverContext?.server.ip_address : 'Unavailable'}</dd>
            </dl>
            <dl>
              <dt className='dt'>vCores</dt>
              <dd className='dd'>{data.maxcpu}</dd>
            </dl>
            <dl>
              <dt className='dt'>Memory</dt>
              <dd className='dd'>
                {memory.size} {memory.unit}
              </dd>
            </dl>
            <div className='flex flex-col md:flex-row space-y-3 space-x-0 md:space-y-0 md:space-x-9'>
              <dl>
                <dt className='dt'>Disk Capacity</dt>
                <dd className='dd'>
                  {disk.size} {disk.unit}
                </dd>
              </dl>
              <dl>
                <dt className='dt'>Disk Written</dt>
                <dd className='dd'>
                  {diskWritten.size} {diskWritten.unit}
                </dd>
              </dl>
              <dl>
                <dt className='dt'>Disk Read</dt>
                <dd className='dd'>
                  {diskRead.size} {diskRead.unit}
                </dd>
              </dl>
            </div>
            <div className='flex space-x-9'>
              <dl>
                <dt className='dt'>Network Inbound</dt>
                <dd className='dd'>
                  {networkIn.size} {networkIn.unit}
                </dd>
              </dl>
              <dl>
                <dt className='dt'>Network Outbound</dt>
                <dd className='dd'>
                  {networkOut.size} {networkOut.unit}
                </dd>
              </dl>
            </div>
          </>
        )}
        {status !== 'success' && (
          <>
            <dl>
              <dt className='dt'>vCores</dt>
              <dd className='dd'>
                <Skeleton height={16} className='max-w-[4rem]' />
              </dd>
            </dl>
            <dl>
              <dt className='dt'>Memory</dt>
              <dd className='dd'>
                <Skeleton height={16} className='max-w-[4rem]' />
              </dd>
            </dl>
            <div className='flex flex-col md:flex-row space-y-3 space-x-0 md:space-y-0 md:space-x-9'>
              <dl>
                <dt className='dt'>Disk Capacity</dt>
                <dd className='dd'>
                  <Skeleton height={16} className='max-w-[4rem]' />
                </dd>
              </dl>
              <dl>
                <dt className='dt'>Disk Usage</dt>
                <dd className='dd'>
                  <Skeleton height={16} className='max-w-[4rem]' />
                </dd>
              </dl>
            </div>
            <div className='flex space-x-9'>
              <dl>
                <dt className='dt'>Network Inbound</dt>
                <dd className='dd'>
                  <Skeleton height={16} className='max-w-[4rem]' />
                </dd>
              </dl>
              <dl>
                <dt className='dt'>Network Outbound</dt>
                <dd className='dd'>
                  <Skeleton height={16} className='max-w-[4rem]' />
                </dd>
              </dl>
            </div>
          </>
        )}
      </Paper>
    </div>
  )
}

export default ServerResources
