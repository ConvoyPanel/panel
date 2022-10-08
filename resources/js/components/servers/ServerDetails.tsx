import { useQueries, useQuery } from '@tanstack/react-query'
import getResources from '@/api/server/getDetails'
import { ServerContext } from '@/pages/servers/Show'
import { useContext, useMemo } from 'react'
import { Code, Paper, Skeleton } from '@mantine/core'
import { formatBytes } from '@/api/server/getStatus'

const ServerDetails = () => {
  const serverContext = useContext(ServerContext)

  const { data, status } = useQuery(
    [`details-${serverContext!.server.uuidShort}`],
    async () => {
      const { data } = await getResources(serverContext!.server.uuidShort)
      return data
    }
  )

  const memory = formatBytes(data?.limits.memory || 0)
  const disk = formatBytes(data?.limits.disk || 0)
  const diskWritten = formatBytes(data?.usage.disk.write || 0)
  const diskRead = formatBytes(data?.usage.disk.read || 0)
  const networkIn = formatBytes(data?.usage.network.in || 0)
  const networkOut = formatBytes(data?.usage.network.out || 0)

  return (
    <div>
      <h3 className='h3-deemphasized'>Details</h3>
      <Paper shadow='xs' className='p-card mt-3 flex flex-col space-y-3'>
        {status === 'success' && (
          <>
            <dl>
              <dt className='dt'>IP Address</dt>
              <dd className='flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 dd'>
                {data.limits.addresses.ipv4.map((address) => (
                  <Code>{address.address}</Code>
                ))}
                {data.limits.addresses.ipv6.map((address) => (
                  <Code>{address.address}</Code>
                ))}
                {data.limits.addresses.ipv6.length === 0 && data.limits.addresses.ipv4.length === 0
                  ? 'Unavailable'
                  : ''}
              </dd>
            </dl>
            <dl>
              <dt className='dt'>vCores</dt>
              <dd className='dd'>{data.limits.cpu}</dd>
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
              <dt className='dt'>IP Address</dt>
              <dd className='flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 dd'>
                <Skeleton height={16} className='max-w-[4rem]' />
              </dd>
            </dl>
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

export default ServerDetails
