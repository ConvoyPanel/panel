import { useQueries, useQuery } from '@tanstack/react-query'
import getResources from '@/api/server/getDetails'
import { ServerContext } from '@/pages/servers/Show'
import { useContext, useMemo } from 'react'
import { Code, Paper, Skeleton } from '@mantine/core'
import { formatBytes } from '@/api/server/getStatus'
import tw from 'twin.macro'
import styled from 'styled-components'

interface GridProps {
  noBreak?: boolean
}

const Grid = styled.div<GridProps>`
  ${({ noBreak }) =>
    noBreak
      ? tw`flex space-x-9`
      : tw`flex flex-col md:flex-row space-y-3 space-x-0 md:space-y-0 md:space-x-9`}
`

const ServerDetails = () => {
  const serverContext = useContext(ServerContext)

  const { data, status } = useQuery(
    [`details-${serverContext!.server.id}`],
    async () => {
      const { data } = await getResources(serverContext!.server.id)
      return data
    }
  )

  const memory = formatBytes(data?.limits.memory || 0)
  const disk = formatBytes(data?.limits.disk || 0)
  const diskWritten = formatBytes(data?.usage.disk.write || 0)
  const diskRead = formatBytes(data?.usage.disk.read || 0)
  const networkIn = formatBytes(data?.usage.network.in || 0)
  const networkOut = formatBytes(data?.usage.network.out || 0)
  const networkMonthlyTotal = formatBytes(
    data?.usage.network.monthly_total || 0
  )
  const networkLimit = formatBytes(data?.limits.bandwidth_limit || 0)

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
                  <Code key={address.address}>{address.address}</Code>
                ))}
                {data.limits.addresses.ipv6.map((address) => (
                  <Code key={address.address}>{address.address}</Code>
                ))}
                {data.limits.addresses.ipv6.length === 0 &&
                data.limits.addresses.ipv4.length === 0
                  ? 'Unavailable'
                  : ''}
              </dd>
            </dl>
            <dl>
              <dt className='dt'>vCPU</dt>
              <dd className='dd'>{data.limits.cpu}</dd>
            </dl>
            <dl>
              <dt className='dt'>Memory</dt>
              <dd className='dd'>
                {memory.size} {memory.unit}
              </dd>
            </dl>
            <Grid>
              <dl>
                <dt className='dt'>Disk Capacity</dt>
                <dd className='dd'>
                  {disk.size} {disk.unit}
                </dd>
              </dl>
              <Grid noBreak>
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
              </Grid>
            </Grid>
            <Grid>
              <Grid noBreak>
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
              </Grid>
              <Grid noBreak>
                <dl>
                  <dt className='dt'>Network Monthly Total</dt>
                  <dd className='dd'>
                    {networkMonthlyTotal.size} {networkMonthlyTotal.unit}
                  </dd>
                </dl>
                <dl>
                  <dt className='dt'>Network Quota</dt>
                  <dd className='dd'>
                    {networkLimit.size} {networkLimit.unit}
                  </dd>
                </dl>
              </Grid>
            </Grid>
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
            <Grid>
              <dl>
                <dt className='dt'>Disk Capacity</dt>
                <dd className='dd'>
                  <Skeleton height={16} className='max-w-[4rem]' />
                </dd>
              </dl>
              <Grid noBreak>
                <dl>
                  <dt className='dt'>Disk Written</dt>
                  <dd className='dd'>
                    <Skeleton height={16} className='max-w-[4rem]' />
                  </dd>
                </dl>
                <dl>
                  <dt className='dt'>Disk Read</dt>
                  <dd className='dd'>
                    <Skeleton height={16} className='max-w-[4rem]' />
                  </dd>
                </dl>
              </Grid>
            </Grid>
            <Grid>
              <Grid noBreak>
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
              </Grid>
              <Grid noBreak>
                <dl>
                  <dt className='dt'>Network Monthly Total</dt>
                  <dd className='dd'>
                    <Skeleton height={16} className='max-w-[4rem]' />
                  </dd>
                </dl>
                <dl>
                  <dt className='dt'>Network Quota</dt>
                  <dd className='dd'>
                    <Skeleton height={16} className='max-w-[4rem]' />
                  </dd>
                </dl>
              </Grid>
            </Grid>
          </>
        )}
      </Paper>
    </div>
  )
}

export default ServerDetails
