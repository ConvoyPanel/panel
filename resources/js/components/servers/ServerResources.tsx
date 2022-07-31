import { useQueries, useQuery } from '@tanstack/react-query'
import getResources from '@/api/server/getResources'
import { ServerContext } from '@/pages/servers/Show'
import { useContext, useMemo } from 'react'
import { Code, Paper, Skeleton } from '@mantine/core'
import { formatBytes } from '@/api/server/getStatus'
import getCloudinitDump from '@/api/server/settings/getCloudinitDump'

const ServerResources = () => {
  const serverContext = useContext(ServerContext)

  const { data: resourceData, status: resourceStatus } = useQuery(['resources'], async () => {
    const { data } = await getResources(serverContext!.server.id)
    return data
  })

  const { data: dumpData, status: dumpStatus } = useQuery(['dump'], async () => {
    const { data } = await getCloudinitDump(serverContext!.server.id)
    return data
  })

  const addresses = useMemo(() => {
    if (dumpStatus !== 'success') return;

    // find config that has the property subnets
    const config = dumpData.config.find(c => c?.subnets !== undefined)

    if (!config) return undefined

    return config!.subnets!.map(subnet => subnet['address'])

  }, [dumpData, dumpStatus])

  const memory = formatBytes(resourceData?.maxmem || 0)
  const disk = formatBytes(resourceData?.maxdisk || 0)
  const diskWritten = formatBytes(resourceData?.diskwrite || 0)
  const diskRead = formatBytes(resourceData?.diskread || 0)
  const networkIn = formatBytes(resourceData?.netin || 0)
  const networkOut = formatBytes(resourceData?.netout || 0)

  return (
    <div>
      <h3 className='h3-deemphasized'>Specifications</h3>
      <Paper shadow='xs' className='p-card mt-3 flex flex-col space-y-3'>
        {resourceStatus === 'success' && (
          <>
            <dl>
              <dt className='dt'>IP Address</dt>
              <dd className='flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 dd'>{addresses ? addresses.map(address => <Code key={address}>{address}</Code>) : 'Unavailable'}</dd>
            </dl>
            <dl>
              <dt className='dt'>vCores</dt>
              <dd className='dd'>{resourceData!.maxcpu}</dd>
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
        {resourceStatus !== 'success' && (
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
