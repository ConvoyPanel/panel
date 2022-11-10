import { ServerContext } from '@/state/server'
import {
  capitalize,
  convertTimeToSmallest,
  formatBytes,
  Sizes,
} from '@/util/helpers'
import useNotify from '@/util/useNotify'
import styled from '@emotion/styled'
import { Badge, RingProgress, Skeleton } from '@mantine/core'
import { useEffect, useMemo, useRef } from 'react'
import tw from 'twin.macro'
import Card from '@/components/elements/Card'
import { Line } from 'react-chartjs-2'
import { useChartTickLabel } from '@/util/chart'

export const StatRow = styled.div`
  ${tw`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 border border-[#eaeaea] dark:border-[#333333] shadow-light dark:shadow-none rounded bg-white dark:bg-black`}

  &>div {
    ${tw`border-[#eaeaea] dark:border-[#333333] p-6`}
  }

  & > div > p {
    ${tw`font-semibold text-sm text-black dark:text-stone-400`}
  }

  & > div:not(:last-child) {
    ${tw`md:border-r border-[#eaeaea] dark:border-[#333333]`}
  }

  & > div:nth-of-type(-n + 2) {
    ${tw`border-b lg:border-b-0`}
  }

  & > div:nth-of-type(2) {
    ${tw`md:border-r-0 lg:border-r`}
  }

  & > div:nth-of-type(3) {
    ${tw`border-b md:border-b-0`}
  }

  & > div > p:nth-of-type(2) {
    ${tw`text-2xl font-semibold mt-1 dark:text-white`}
  }
`

const ServerDetailsBlock = () => {
  const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid)
  const server = ServerContext.useStoreState((state) => state.server.data!)
  const status = ServerContext.useStoreState((state) => state.status.data)
  const getStatus = ServerContext.useStoreActions(
    (actions) => actions.status.getStatus
  )
  const isUpdating = useRef(true)
  const notify = useNotify()

  useEffect(() => {
    isUpdating.current = true

    const update = async () => {
      if (!isUpdating.current) return

      try {
        await getStatus(uuid)
      } catch {
        notify({
          title: 'Error',
          message: 'Failed to update server status. Retrying in 5 seconds.',
          color: 'red',
        })

        setTimeout(update, 5000)

        return
      }

      setTimeout(update, 1000)
    }

    update()

    return () => {
      isUpdating.current = false
    }
  }, [])

  const memory = useMemo(
    () => ({
      used: formatBytes(status?.memory || 0),
      total: formatBytes(server.limits.memory),
    }),
    [status]
  )

  const uptime = useMemo(
    () => convertTimeToSmallest(status?.uptime || 0),
    [status]
  )

  const bandwidth = useMemo(() => {
    const total = server.limits.bandwidth
      ? formatBytes(server.limits.bandwidth)
      : undefined
    const percentage = server.limits.bandwidth
      ? Math.floor(
          (server.usages.bandwidth / server.limits.bandwidth) * 10000
        ) / 100
      : 0
    const used = formatBytes(server.usages.bandwidth, undefined, total?.unit ? total.unit : undefined)

    return {used, total, percentage}
  }, [server])

  const cpuGraph = useChartTickLabel('CPU', 100, '%', 2)
  const memoryGraph = useChartTickLabel(
    'Memory',
    memory.total.size,
    memory.total.unit,
    2
  )

  useEffect(() => {
    if (status) {
      cpuGraph.push(Math.floor(status.cpu * 10000) / 100)
      memoryGraph.push(
        formatBytes(status.memory, 2, memory.total.unit as Sizes).size
      )
    }
  }, [status])

  return (
    <>
      {!status ? (
        <Skeleton className='w-full col-span-10 !h-[421px] md:!h-[211px] lg:!h-[106px]' />
      ) : (
        <StatRow className='col-span-10'>
          <div>
            <p>Server State</p>
            <div className='flex space-x-2 items-center mt-1'>
              <div className='grid place-items-center h-full'>
                <div
                  className={`w-3 h-3 rounded-full transition-colors ${
                    status.state === 'running' ? 'bg-green-500' : 'bg-red-500'
                  }`}
                ></div>
              </div>
              <p className='text-2xl font-semibold dark:text-white'>
                {capitalize(status.state)}
              </p>
            </div>
          </div>
          <div>
            <p>CPU Usage</p>
            <p>{Math.floor(status.cpu * 100)}%</p>
          </div>
          <div>
            <p>Memory Usage</p>
            <div className='flex space-x-2 items-end mt-1'>
              <p className='text-2xl font-semibold dark:text-white'>
                {memory.used.size} {memory.used.unit}
              </p>
              <p className='text-sm font-semibold description mb-[0.3rem]'>
                / {memory.total.size} {memory.total.unit}
              </p>
            </div>
          </div>
          <div>
            <p>Uptime</p>
            <div className='flex space-x-2 items-end mt-1'>
              <p className='text-2xl font-semibold dark:text-white'>
                {Math.floor(uptime.time)}
              </p>
              <p className='text-sm font-semibold description mb-[0.3rem]'>
                {uptime.unit}
              </p>
            </div>
          </div>
        </StatRow>
      )}

      <Card className='flex flex-col justify-between items-center col-span-10 lg:col-span-2'>
        <h5 className='h5'>Bandwidth Usage</h5>
        <div className='grid place-items-center mt-5'>
          <h4 className='absolute text-3xl font-semibold text-auto'>
            {Math.floor(bandwidth.percentage)}
          </h4>
          <RingProgress
            size={128}
            thickness={12}
            roundCaps
            sections={[
              {
                value: bandwidth.percentage,
                color: bandwidth.percentage < 100 ? 'green' : 'yellow',
              },
            ]}
          />
        </div>
        <Badge
          className='!normal-case'
          size='lg'
          color='gray'
          variant='outline'
        >
          {bandwidth.used.size} /{' '}
          {bandwidth.total
            ? `${bandwidth.total.size} ${bandwidth.total.unit}`
            : 'unlimited'}
        </Badge>
      </Card>

      <Card className='flex flex-col space-y-5 col-span-10 md:col-span-5 lg:col-span-4'>
        <h5 className='h5'>CPU</h5>
        <Line {...cpuGraph.props} />
      </Card>
      <Card className='flex flex-col space-y-5 col-span-10 md:col-span-5 lg:col-span-4'>
        <h5 className='h5'>Memory</h5>
        <Line {...memoryGraph.props} />
      </Card>
    </>
  )
}

export default ServerDetailsBlock
